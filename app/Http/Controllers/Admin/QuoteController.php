<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\WorkOrder;
use App\Notifications\QuoteSentToClient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuoteController extends Controller
{
    /** Crea un borrador nuevo: vacío o copiando la última versión con precios actuales. */
    public function store(Request $request, WorkOrder $trabajo): RedirectResponse
    {
        $latest = $trabajo->quotes()->with('items.material')->first();

        $quote = $latest && $request->boolean('copy')
            ? $latest->duplicateAsNewVersion($request->user())
            : DB::transaction(fn () => $trabajo->quotes()->create([
                'version' => (int) $trabajo->quotes()->lockForUpdate()->max('version') + 1,
                'status' => QuoteStatus::Borrador,
                'margin_pct' => config('carpinteria.default_margin_pct'),
                'tax_pct' => config('carpinteria.default_tax_pct'),
                'valid_until' => today()->addDays(15),
                'created_by' => $request->user()->id,
            ]));

        return redirect()->route('admin.presupuestos.edit', $quote);
    }

    public function edit(Quote $quote): View
    {
        $quote->load(['workOrder.client', 'items.material']);

        return view('admin.presupuestos.edit', [
            'quote' => $quote,
            'materials' => Material::query()->where('is_active', true)->with('category')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Quote $quote): RedirectResponse
    {
        $this->ensureEditable($quote);
        $quote->fill($request->validate([
            'labor_cost' => ['required', 'numeric', 'min:0'],
            'margin_pct' => ['required', 'numeric', 'between:0,500'],
            'tax_pct' => ['required', 'numeric', 'between:0,100'],
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], [], ['labor_cost' => 'mano de obra', 'margin_pct' => 'margen', 'tax_pct' => 'IVA', 'valid_until' => 'validez']));
        $quote->recalculate();

        return back()->with('status', 'Presupuesto actualizado.');
    }

    public function addItem(Request $request, Quote $quote): RedirectResponse
    {
        $this->ensureEditable($quote);
        $data = $request->validate([
            'material_id' => ['nullable', 'exists:materials,id'],
            'description' => ['required_without:material_id', 'nullable', 'string', 'max:255'],
            'qty' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'unit' => ['required_without:material_id', 'nullable', 'string', 'max:20'],
            'unit_price' => ['required_without:material_id', 'nullable', 'numeric', 'min:0'],
        ], [], ['description' => 'descripción', 'qty' => 'cantidad', 'unit' => 'unidad', 'unit_price' => 'precio unitario']);

        $material = isset($data['material_id']) ? Material::find($data['material_id']) : null;
        $price = $material ? (float) $material->current_price : (float) $data['unit_price'];

        $quote->items()->create([
            'material_id' => $material?->id,
            'description' => ($data['description'] ?? null) ?: $material?->name,
            'qty' => $data['qty'],
            'unit' => $material?->unit->label() ?? $data['unit'],
            'unit_price' => $price,
            'line_total' => round($price * (float) $data['qty'], 2),
        ]);
        $quote->recalculate();

        return back()->with('status', 'Ítem agregado.');
    }

    public function removeItem(Quote $quote, QuoteItem $item): RedirectResponse
    {
        $this->ensureEditable($quote);
        abort_unless($item->quote_id === $quote->id, 404);
        $item->delete();
        $quote->recalculate();

        return back()->with('status', 'Ítem quitado.');
    }

    public function send(Quote $quote): RedirectResponse
    {
        $this->ensureEditable($quote);
        $quote->load('items');
        if ($quote->items->isEmpty() && (float) $quote->labor_cost == 0) {
            throw ValidationException::withMessages(['quote' => 'El presupuesto está vacío.']);
        }

        // Una versión nueva reemplaza a las enviadas que seguían sin respuesta.
        $quote->workOrder->quotes()->where('status', QuoteStatus::Enviado)->update(['status' => QuoteStatus::Vencido]);
        $quote->update(['status' => QuoteStatus::Enviado, 'sent_at' => now()]);

        $clientUser = $quote->workOrder->client->user;
        $clientUser?->notify(new QuoteSentToClient($quote));

        return redirect()->route('admin.trabajos.show', $quote->workOrder)->with('status', $clientUser
            ? "Presupuesto v{$quote->version} enviado. Le avisamos al cliente por email."
            : "Presupuesto v{$quote->version} marcado como enviado. El cliente no tiene acceso al portal: mandale el PDF.");
    }

    public function destroy(Quote $quote): RedirectResponse
    {
        $this->ensureEditable($quote);
        $order = $quote->workOrder;
        $quote->delete();

        return redirect()->route('admin.trabajos.show', $order)->with('status', 'Borrador eliminado.');
    }

    /** PDF interno con el detalle de costos. */
    public function pdf(Quote $quote): Response
    {
        $quote->load(['workOrder.client.gatedCommunity', 'items']);

        return Pdf::loadView('presupuestos.pdf', ['quote' => $quote, 'internal' => true])
            ->download("presupuesto-{$quote->workOrder->code}-v{$quote->version}-interno.pdf");
    }

    private function ensureEditable(Quote $quote): void
    {
        if (! $quote->isEditable()) {
            throw ValidationException::withMessages(['quote' => 'Este presupuesto ya se envió. Creá una versión nueva para cambiarlo.']);
        }
    }
}
