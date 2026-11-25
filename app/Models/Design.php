<?php

namespace App\Models;

use App\Enums\DesignStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Versión del diseño SketchUp de un trabajo. El cliente aprueba o pide cambios desde el portal. */
#[Fillable(['version', 'skp_path', 'preview_path', 'notes', 'status', 'sent_at', 'client_feedback', 'responded_at', 'approved_at', 'approved_by'])]
class Design extends Model
{
    protected function casts(): array
    {
        return [
            'status' => DesignStatus::class,
            'sent_at' => 'datetime',
            'responded_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** El cliente solo ve versiones que se le enviaron. */
    public function isVisibleToClient(): bool
    {
        return $this->status !== DesignStatus::Borrador;
    }

    public function awaitsClient(): bool
    {
        return $this->status === DesignStatus::Enviado;
    }
}
