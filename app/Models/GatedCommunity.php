<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable(['name', 'city', 'access_notes', 'show_on_website'])]
class GatedCommunity extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['show_on_website' => 'boolean'];
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    /** Documentos generales que el barrio exige para entrar (ART, seguro, DNI...). */
    public function requiredDocumentTypes(): BelongsToMany
    {
        return $this->belongsToMany(DocumentType::class, 'document_type_gated_community');
    }

    /** Documentos propios del barrio (permiso, credencial): siempre exigidos. */
    public function ownDocumentTypes(): HasMany
    {
        return $this->hasMany(DocumentType::class);
    }

    /** Todo lo que el barrio pide a cada empleado. */
    public function requiredTypes(): Collection
    {
        return $this->requiredDocumentTypes->concat($this->ownDocumentTypes)->sortBy('name')->values();
    }
}
