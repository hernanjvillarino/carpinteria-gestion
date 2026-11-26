<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/** Persona del taller que va a las obras. Puede o no tener acceso al sistema. */
#[Fillable(['user_id', 'name', 'dni', 'cuil', 'phone', 'notes', 'is_active'])]
class Employee extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Documento que vale para ese tipo: el de vencimiento más lejano
     * (uno sin vencimiento le gana a todos). Usa la relación ya cargada.
     */
    public function currentDocument(DocumentType $type): ?EmployeeDocument
    {
        return $this->documents
            ->where('document_type_id', $type->id)
            ->sortByDesc(fn (EmployeeDocument $d) => [$d->expires_on ? $d->expires_on->format('Ymd') : '99999999', $d->id])
            ->first();
    }

    /** El documento que vale de cada tipo cargado, sin los reemplazados. */
    public function currentDocuments(): Collection
    {
        return $this->documents->groupBy('document_type_id')
            ->map(fn (Collection $docs) => $this->currentDocument($docs->first()->type))
            ->values();
    }

    public function statusFor(DocumentType $type): DocumentStatus
    {
        return $this->currentDocument($type)?->status() ?? DocumentStatus::Falta;
    }

    /** Documentos exigidos por el barrio que le faltan o están vencidos. */
    public function missingFor(GatedCommunity $barrio): Collection
    {
        return $barrio->requiredTypes()->reject(fn (DocumentType $type) => $this->statusFor($type)->allowsEntry())->values();
    }

    public function isEnabledFor(GatedCommunity $barrio): bool
    {
        return $this->missingFor($barrio)->isEmpty();
    }
}
