<?php

namespace App\Models;

use App\Enums\CorteOptMaterial;
use App\Enums\MaterialUnit;
use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** Insumo con stock y precio: placas, cantos, herrajes, insumos. */
#[Fillable([
    'material_category_id', 'supplier_id', 'sku', 'name', 'unit', 'thickness_mm', 'corteopt_material',
    'board_color_id', 'characteristic', 'brand', 'sheet_length_mm', 'sheet_width_mm', 'has_grain',
    'cutlist_alias', 'min_stock', 'is_active',
])]
class Material extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'unit' => MaterialUnit::class,
            'corteopt_material' => CorteOptMaterial::class,
            'thickness_mm' => 'decimal:1',
            'has_grain' => 'boolean',
            'is_active' => 'boolean',
            'stock_qty' => 'decimal:3',
            'min_stock' => 'decimal:3',
            'current_price' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MaterialCategory::class, 'material_category_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(BoardColor::class, 'board_color_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(MaterialPrice::class)->orderByDesc('valid_from')->orderByDesc('id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest()->latest('id');
    }

    public function isBelowMinimum(): bool
    {
        return (float) $this->stock_qty < (float) $this->min_stock;
    }

    public function scopeBelowMinimum($query)
    {
        return $query->where('is_active', true)->whereColumn('stock_qty', '<', 'min_stock');
    }

    /** Nombre completo para listas: "Melamina 18 mm Blanco". */
    public function displayName(): string
    {
        return $this->name;
    }

    /** Cambia el precio vigente y lo guarda en el historial. */
    public function setPrice(float $price, ?User $by = null): void
    {
        if ($price < 0) {
            throw new InvalidArgumentException('El precio no puede ser negativo.');
        }
        if ($this->exists && (float) $this->current_price === $price && $this->prices()->exists()) {
            return;
        }

        DB::transaction(function () use ($price, $by) {
            $this->forceFill(['current_price' => $price])->save();
            $this->prices()->create(['price' => $price, 'valid_from' => today(), 'created_by' => $by?->id]);
        });
    }

    /**
     * Registra un movimiento y actualiza el stock. La cantidad se pasa siempre positiva
     * para ingresos y consumos; los ajustes pueden ser negativos.
     */
    public function move(StockMovementType $type, float $qty, User $by, ?WorkOrder $order = null, ?string $note = null, ?float $unitCost = null): StockMovement
    {
        $signed = match ($type) {
            StockMovementType::Ingreso => abs($qty),
            StockMovementType::Consumo => -abs($qty),
            StockMovementType::Ajuste => $qty,
        };
        if ($signed == 0) {
            throw new InvalidArgumentException('La cantidad no puede ser cero.');
        }

        return DB::transaction(function () use ($type, $signed, $by, $order, $note, $unitCost) {
            $material = static::query()->lockForUpdate()->findOrFail($this->id);
            $material->forceFill(['stock_qty' => (float) $material->stock_qty + $signed])->save();
            $this->stock_qty = $material->stock_qty;

            return $this->movements()->create([
                'type' => $type,
                'qty' => $signed,
                'unit_cost' => $unitCost ?? ($type === StockMovementType::Consumo ? (float) $this->current_price : null),
                'work_order_id' => $order?->id,
                'note' => $note,
                'user_id' => $by->id,
            ]);
        });
    }
}
