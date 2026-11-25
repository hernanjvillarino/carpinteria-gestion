<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Presupuesto de un trabajo. Los precios de los ítems se copian del stock al cargarlos,
 * así un cambio de precio posterior no altera un presupuesto ya armado.
 *
 * subtotal = (ítems + mano de obra) × (1 + margen)
 * total    = subtotal × (1 + IVA)
 */
#[Fillable(['version', 'status', 'labor_cost', 'margin_pct', 'tax_pct', 'subtotal', 'total', 'valid_until', 'notes', 'sent_at', 'accepted_at', 'client_feedback', 'responded_at', 'responded_by', 'created_by'])]
class Quote extends Model
{
    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'labor_cost' => 'decimal:2',
            'margin_pct' => 'decimal:2',
            'tax_pct' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'valid_until' => 'date',
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isEditable(): bool
    {
        return $this->status === QuoteStatus::Borrador;
    }

    /** Vencido para el cliente si pasó la fecha de validez sin respuesta. */
    public function isExpired(): bool
    {
        return $this->status === QuoteStatus::Enviado && $this->valid_until && $this->valid_until->endOfDay()->isPast();
    }

    public function itemsTotal(): float
    {
        return round((float) $this->items->sum('line_total'), 2);
    }

    public function taxAmount(): float
    {
        return round((float) $this->total - (float) $this->subtotal, 2);
    }

    public function recalculate(): void
    {
        $this->load('items');
        $base = $this->itemsTotal() + (float) $this->labor_cost;
        $this->subtotal = round($base * (1 + (float) $this->margin_pct / 100), 2);
        $this->total = round((float) $this->subtotal * (1 + (float) $this->tax_pct / 100), 2);
        $this->save();
    }

    /** Copia este presupuesto como borrador de una versión nueva, con los precios actuales del stock. */
    public function duplicateAsNewVersion(User $by): self
    {
        return DB::transaction(function () use ($by) {
            $copy = $this->workOrder->quotes()->create([
                'version' => (int) $this->workOrder->quotes()->lockForUpdate()->max('version') + 1,
                'status' => QuoteStatus::Borrador,
                'labor_cost' => $this->labor_cost,
                'margin_pct' => $this->margin_pct,
                'tax_pct' => $this->tax_pct,
                'valid_until' => today()->addDays(15),
                'notes' => $this->notes,
                'created_by' => $by->id,
            ]);

            foreach ($this->items as $item) {
                $price = $item->material ? (float) $item->material->current_price : (float) $item->unit_price;
                $copy->items()->create([
                    'material_id' => $item->material_id,
                    'description' => $item->description,
                    'qty' => $item->qty,
                    'unit' => $item->unit,
                    'unit_price' => $price,
                    'line_total' => round($price * (float) $item->qty, 2),
                ]);
            }
            $copy->recalculate();

            return $copy;
        });
    }
}
