<?php

namespace App\Models;

use App\Enums\DesignStatus;
use App\Enums\Priority;
use App\Notifications\WorkOrderStageChanged;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * Un trabajo (pedido) de un cliente. Se llama WorkOrder porque Laravel reserva "jobs" para las colas.
 */
#[Fillable(['code', 'client_id', 'title', 'description', 'install_address', 'stage_id', 'priority', 'estimated_delivery', 'delivered_at', 'created_by'])]
class WorkOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'priority' => Priority::class,
            'estimated_delivery' => 'date',
            'delivered_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (WorkOrder $order) {
            $order->code ??= static::nextCode();
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

    public function approvedDesign(): ?Design
    {
        return $this->designs()->where('status', DesignStatus::Aprobado)->first();
    }

    public function isAssignedTo(User $user): bool
    {
        return $this->carpenters()->whereKey($user->id)->exists();
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

        return $update;
    }
}
