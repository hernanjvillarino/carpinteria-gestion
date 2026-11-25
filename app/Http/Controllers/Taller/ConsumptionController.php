<?php

namespace App\Http\Controllers\Taller;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\WorkOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** El carpintero (o el admin) registra materiales usados en un trabajo. */
class ConsumptionController extends Controller
{
    public function store(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        Gate::authorize('progress', $workOrder);

        $data = $request->validate([
            'material_id' => ['required', 'exists:materials,id'],
            'qty' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [], ['material_id' => 'material', 'qty' => 'cantidad']);

        $material = Material::findOrFail($data['material_id']);
        $material->move(StockMovementType::Consumo, (float) $data['qty'], $request->user(), $workOrder, $data['note'] ?? null);

        $msg = "Consumo de {$material->name} registrado.";
        if ($material->fresh()->isBelowMinimum()) {
            $msg .= ' Ojo: quedó por debajo del stock mínimo.';
        }

        return back()->with('status', $msg);
    }
}
