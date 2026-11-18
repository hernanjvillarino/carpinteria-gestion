<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'position', 'visible_to_client'])]
class Stage extends Model
{
    protected function casts(): array
    {
        return ['visible_to_client' => 'boolean'];
    }

    public static function ordered()
    {
        return static::query()->orderBy('position')->get();
    }

    public function next(): ?self
    {
        return static::query()->where('position', '>', $this->position)->orderBy('position')->first();
    }

    public function isLast(): bool
    {
        return $this->next() === null;
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }
}
