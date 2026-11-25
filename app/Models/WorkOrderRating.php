<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Calificación del cliente sobre un trabajo entregado (uso interno, mejora continua). */
#[Fillable(['user_id', 'score_work', 'score_materials', 'score_installation', 'score_staff', 'score_service', 'comment'])]
class WorkOrderRating extends Model
{
    /** Rubros que califica el cliente: columna => etiqueta. */
    public const CRITERIA = [
        'score_work' => 'Trabajo terminado',
        'score_materials' => 'Materiales',
        'score_installation' => 'Instalación',
        'score_staff' => 'Personal',
        'score_service' => 'Atención',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(WorkOrderRatingPhoto::class);
    }

    public function average(): float
    {
        return round(collect(array_keys(self::CRITERIA))->avg(fn ($c) => $this->{$c}), 1);
    }
}
