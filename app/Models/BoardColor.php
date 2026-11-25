<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Lista cerrada de colores de placa (ver docs/corteopt-importacion.md). */
#[Fillable(['name', 'is_active'])]
class BoardColor extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
