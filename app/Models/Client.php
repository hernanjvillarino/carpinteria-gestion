<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'name', 'email', 'phone', 'tax_id', 'address', 'gated_community_id', 'lot', 'notes'])]
class Client extends Model
{
    use HasFactory, SoftDeletes;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gatedCommunity(): BelongsTo
    {
        return $this->belongsTo(GatedCommunity::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    /** Dirección legible: barrio + lote o dirección libre. */
    public function locationLabel(): string
    {
        if ($this->gatedCommunity) {
            return trim($this->gatedCommunity->name.($this->lot ? ', lote '.$this->lot : ''));
        }

        return (string) $this->address;
    }
}
