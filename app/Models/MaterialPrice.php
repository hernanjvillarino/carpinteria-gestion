<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['price', 'valid_from', 'created_by'])]
class MaterialPrice extends Model
{
    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'valid_from' => 'date'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
