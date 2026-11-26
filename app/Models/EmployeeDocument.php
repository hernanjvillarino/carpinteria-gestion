<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/** Archivo de un empleado (se guarda en el disco privado). */
#[Fillable(['employee_id', 'document_type_id', 'path', 'original_name', 'mime', 'size', 'expires_on', 'notes', 'uploaded_by', 'expiring_notified_at', 'expired_notified_at'])]
class EmployeeDocument extends Model
{
    protected function casts(): array
    {
        return [
            'expires_on' => 'date',
            'expiring_notified_at' => 'datetime',
            'expired_notified_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** Vence al terminar el día indicado: el mismo día todavía sirve. */
    public function status(?Carbon $today = null): DocumentStatus
    {
        if (! $this->expires_on) {
            return DocumentStatus::Vigente;
        }
        $today ??= today();
        if ($this->expires_on->lt($today)) {
            return DocumentStatus::Vencido;
        }

        return $this->expires_on->lte($today->copy()->addDays(config('carpinteria.document_warning_days')))
            ? DocumentStatus::PorVencer
            : DocumentStatus::Vigente;
    }

    public function daysLeft(): ?int
    {
        return $this->expires_on ? (int) today()->diffInDays($this->expires_on, false) : null;
    }
}
