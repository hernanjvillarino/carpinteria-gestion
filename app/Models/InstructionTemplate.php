<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Indicación precargada por el dueño para tildar al asignar un trabajo. */
#[Fillable(['text', 'position', 'is_active'])]
class InstructionTemplate extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('position')->orderBy('id');
    }
}
