<?php

namespace App\Models;

use App\Enums\DesignStatus;
use App\Enums\Priority;
use App\Enums\Role;
use App\Notifications\RatingRequested;
use App\Notifications\WorkOrderAssigned;
use App\Notifications\WorkOrderProgressed;
use App\Notifications\WorkOrderStageChanged;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Un trabajo (pedido) de un cliente. Se llama WorkOrder porque Laravel reserva "jobs" para las colas.
 */
#[Fillable(['code', 'client_id', 'title', 'description', 'install_address', 'stage_id', 'priority', 'lead_time_days', 'started_on', 'estimated_delivery', 'delivered_at', 'created_by'])]
class WorkOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'priority' => Priority::class,
            'lead_time_days' => 'integer',
            'started_on' => 'date',
            'estimated_delivery' => 'date',
            'delivered_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (WorkOrder $order) {
            $order->code ??= static::nextCode();
        });

        // La entrega estimada siempre sale del inicio + el plazo pactado con el cliente.
        static::saving(function (WorkOrder $order) {
            if (! $order->exists) {
                $order->lead_time_days ??= config('carpinteria.default_lead_time_days');
                $order->started_on ??= today();
            }
            if ($order->isDirty(['lead_time_days', 'started_on']) || ! $order->estimated_delivery) {
                $order->estimated_delivery = $order->started_on?->copy()->addDays((int) $order->lead_time_days);
            }
        });
    }

    /** Código correlativo por año: T-2026-0001. */
    public static function nextCode(): string
    {
        $prefix = 'T-'.now()->year.'-';
        $last = static::withTrashed()->where('code', 'like', $prefix.'%')->orderByDesc('code')->value('code');
        $number = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function carpenters(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'work_order_user')->withPivot('is_lead')->withTimestamps();
    }

    public function updates(): HasMany
    {
        return $this->hasMany(WorkOrderUpdate::class)->latest()->latest('id');
    }

    public function designs(): HasMany
    {
        return $this->hasMany(Design::class)->orderByDesc('version');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class)->orderByDesc('version');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest()->latest('id');
    }

    public function rating(): HasOne
    {
        return $this->hasOne(WorkOrderRating::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(WorkOrderTask::class)->orderBy('position')->orderBy('id');
    }

    public function approvedDesign(): ?Design
    {
        return $this->designs()->where('status', DesignStatus::Aprobado)->first();
    }

    public function isAssignedTo(User $user): bool
    {
        return $this->carpenters()->whereKey($user->id)->exists();
    }

    /** Avance del trabajo según la etapa en la que está (0 a 100). */
    public function progressPct(int $stageCount, int $stagePosition): int
    {
        if ($this->delivered_at) {
            return 100;
        }

        return $stageCount > 1 ? (int) round(($stagePosition - 1) / ($stageCount - 1) * 100) : 0;
    }

    /**
     * Asigna carpinteros y avisa a los que se suman (con número y nombre del trabajo).
     *
     * @param  array<int, int|string>  $carpenterIds
     */
    public function assignCarpenters(array $carpenterIds, ?int $leadId = null): void
    {
        $before = $this->carpenters()->pluck('users.id')->all();

        $this->carpenters()->sync(
            collect($carpenterIds)->mapWithKeys(fn ($id) => [(int) $id => ['is_lead' => (int) $id === $leadId]])->all()
        );

        $added = array_diff(array_map('intval', $carpenterIds), $before);
        if ($added) {
            Notification::send(User::query()->whereKey($added)->get(), new WorkOrderAssigned($this));
        }
    }

    /**
     * Suma tareas al trabajo: indicaciones precargadas tildadas y/o texto libre (una por línea).
     *
     * @param  array<int, int|string>  $templateIds
     */
    public function addTasks(User $by, array $templateIds = [], ?string $freeText = null): void
    {
        $position = (int) $this->tasks()->max('position');
        $existing = $this->tasks()->whereNotNull('instruction_template_id')->pluck('instruction_template_id')->all();

        $templates = InstructionTemplate::query()->whereKey($templateIds)->whereNotIn('id', $existing)->orderBy('position')->get();
        foreach ($templates as $template) {
            $this->tasks()->create([
                'instruction_template_id' => $template->id,
                'text' => $template->text,
                'position' => ++$position,
                'created_by' => $by->id,
            ]);
        }

        foreach (preg_split('/\R/', (string) $freeText) as $line) {
            if (trim($line) !== '') {
                $this->tasks()->create(['text' => mb_substr(trim($line), 0, 500), 'position' => ++$position, 'created_by' => $by->id]);
            }
        }
    }

    /**
     * Registra un avance: cambio de etapa opcional, comentario y fotos.
     *
     * @param  array<int, array{path: string, caption?: ?string}>  $photos
     */
    public function recordUpdate(User $by, ?Stage $toStage, ?string $comment, bool $visibleToClient, array $photos = []): WorkOrderUpdate
    {
        $update = DB::transaction(function () use ($by, $toStage, $comment, $visibleToClient, $photos) {
            $fromStageId = $this->stage_id;
            $changesStage = $toStage && $toStage->id !== $fromStageId;

            $update = $this->updates()->create([
                'user_id' => $by->id,
                'from_stage_id' => $changesStage ? $fromStageId : null,
                'to_stage_id' => $changesStage ? $toStage->id : null,
                'comment' => $comment,
                'visible_to_client' => $visibleToClient,
            ]);

            foreach ($photos as $photo) {
                $update->photos()->create([
                    'path' => $photo['path'],
                    'caption' => $photo['caption'] ?? null,
                    'visible_to_client' => $visibleToClient,
                ]);
            }

            if ($changesStage) {
                $this->stage()->associate($toStage);
                if ($toStage->isLast() && ! $this->delivered_at) {
                    $this->delivered_at = now();
                }
                $this->save();
            }

            return $update;
        });

        if ($update->to_stage_id && $toStage->visible_to_client) {
            $this->client->user?->notify(new WorkOrderStageChanged($this, $toStage));
        }

        // Al entregar el trabajo, se le pide al cliente que lo califique.
        if ($update->to_stage_id && $toStage->isLast()) {
            $this->client->user?->notify(new RatingRequested($this));
        }

        // El dueño se entera de cada avance que carga un carpintero.
        if ($by->role === Role::Carpintero) {
            Notification::send(User::query()->admins()->get(), new WorkOrderProgressed($update));
        }

        return $update;
    }
}
