<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CorteOptMaterial;
use App\Enums\MaterialUnit;
use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Models\BoardColor;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\Supplier;
use App\Models\WorkOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MaterialController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'categoria' => ['nullable', 'integer'],
            'bajo_minimo' => ['nullable', 'boolean'],
        ]);

        return view('admin.stock.index', [
            'materials' => Material::query()->with(['category', 'supplier', 'color'])
                ->when($filters['q'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%")))
                ->when($filters['categoria'] ?? null, fn ($q, $id) => $q->where('material_category_id', $id))
                ->when($filters['bajo_minimo'] ?? false, fn ($q) => $q->belowMinimum())
                ->orderBy('name')->paginate(50)->withQueryString(),
            'categories' => MaterialCategory::orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('admin.stock.form', $this->formData(new Material(['unit' => MaterialUnit::Unidad, 'is_active' => true])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $material = DB::transaction(function () use ($data, $request) {
            $material = Material::create(collect($data)->except(['price', 'initial_stock'])->all());
            $material->setPrice((float) $data['price'], $request->user());
            if (($data['initial_stock'] ?? 0) > 0) {
                $material->move(StockMovementType::Ingreso, (float) $data['initial_stock'], $request->user(), note: 'Stock inicial');
            }

            return $material;
        });

        return redirect()->route('admin.stock.show', $material)->with('status', 'Insumo creado.');
    }

    public function show(Material $material): View
    {
        $material->load(['category', 'supplier', 'color', 'prices.creator', 'movements' => fn ($q) => $q->with(['user', 'workOrder'])->limit(50)]);

        return view('admin.stock.show', [
            'material' => $material,
            'types' => StockMovementType::cases(),
            'orders' => WorkOrder::query()->whereNull('delivered_at')->orderByDesc('id')->get(['id', 'code', 'title']),
        ]);
    }

    public function edit(Material $material): View
    {
        return view('admin.stock.form', $this->formData($material));
    }

    public function update(Request $request, Material $material): RedirectResponse
    {
        $data = $this->validated($request, $material);

        DB::transaction(function () use ($material, $data, $request) {
            $material->update(collect($data)->except(['price', 'initial_stock'])->all());
            $material->setPrice((float) $data['price'], $request->user());
        });

        return redirect()->route('admin.stock.show', $material)->with('status', 'Insumo actualizado.');
    }

    /** Ingreso, ajuste o consumo cargado por la administración. */
    public function move(Request $request, Material $material): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::enum(StockMovementType::class)],
            'qty' => ['required', 'numeric', 'not_in:0', 'between:-100000,100000'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'work_order_id' => ['nullable', 'exists:work_orders,id'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [], ['qty' => 'cantidad', 'unit_cost' => 'costo unitario']);

        $type = StockMovementType::from($data['type']);
        $material->move($type, (float) $data['qty'], $request->user(),
            isset($data['work_order_id']) ? WorkOrder::find($data['work_order_id']) : null,
            $data['note'] ?? null, isset($data['unit_cost']) ? (float) $data['unit_cost'] : null);

        if ($type === StockMovementType::Ingreso && isset($data['unit_cost']) && $request->boolean('update_price')) {
            $material->setPrice((float) $data['unit_cost'], $request->user());
        }

        return back()->with('status', 'Movimiento registrado. Stock actual: '.qty($material->fresh()->stock_qty));
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        MaterialCategory::create($request->validate(['name' => ['required', 'string', 'max:80', 'unique:material_categories,name']]));

        return back()->with('status', 'Categoría agregada.');
    }

    public function storeColor(Request $request): RedirectResponse
    {
        BoardColor::create($request->validate(['name' => ['required', 'string', 'max:60', 'unique:board_colors,name']]));

        return back()->with('status', 'Color agregado a la lista.');
    }

    private function formData(Material $material): array
    {
        return [
            'material' => $material,
            'categories' => MaterialCategory::orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'colors' => BoardColor::where('is_active', true)->orderBy('name')->get(),
            'units' => MaterialUnit::cases(),
            'corteoptMaterials' => CorteOptMaterial::cases(),
        ];
    }

    private function validated(Request $request, ?Material $material = null): array
    {
        $isBoard = filled($request->input('corteopt_material'));

        return $request->validate([
            'material_category_id' => ['required', 'exists:material_categories,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'sku' => ['nullable', 'string', 'max:40', Rule::unique('materials')->ignore($material)],
            'name' => ['required', 'string', 'max:150'],
            'unit' => ['required', Rule::enum(MaterialUnit::class)],
            'thickness_mm' => [Rule::requiredIf($isBoard), 'nullable', 'numeric', 'between:2,100'],
            'corteopt_material' => ['nullable', Rule::enum(CorteOptMaterial::class)],
            'board_color_id' => [Rule::requiredIf($isBoard), 'nullable', 'exists:board_colors,id'],
            'characteristic' => ['nullable', 'string', 'max:60'],
            'brand' => ['nullable', 'string', 'max:40'],
            'sheet_length_mm' => ['nullable', 'integer', 'between:100,6000'],
            'sheet_width_mm' => ['nullable', 'integer', 'between:100,6000'],
            'cutlist_alias' => ['nullable', 'string', 'max:150'],
            'min_stock' => ['required', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'initial_stock' => ['nullable', 'numeric', 'min:0'],
        ], [], [
            'material_category_id' => 'categoría', 'thickness_mm' => 'espesor', 'board_color_id' => 'color',
            'min_stock' => 'stock mínimo', 'price' => 'precio', 'corteopt_material' => 'material corteOPT',
        ]) + ['has_grain' => $request->boolean('has_grain'), 'is_active' => $request->boolean('is_active', true)];
    }
}
