<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tipo de documento del personal: ART, seguro de accidentes personales, DNI...
 * Si tiene barrio, es un documento propio de ese barrio (permiso, credencial) y
 * ese barrio lo exige siempre.
 */
#[Fillable(['name', 'requires_expiry', 'gated_community_id'])]
class DocumentType extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['requires_expiry' => 'boolean'];
    }

    /** Barrio dueño del documento, si es propio de un barrio. */
    public function gatedCommunity(): BelongsTo
    {
        return $this->belongsTo(GatedCommunity::class);
    }

    /** Barrios que lo exigen (para los documentos generales). */
    public function requiredBy(): BelongsToMany
    {
        return $this->belongsToMany(GatedCommunity::class, 'document_type_gated_community');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function scopeGeneral(Builder $query): void
    {
        $query->whereNull('gated_community_id');
    }

    public function displayName(): string
    {
        return $this->gated_community_id ? $this->name.' ('.$this->gatedCommunity?->name.')' : $this->name;
    }
}
