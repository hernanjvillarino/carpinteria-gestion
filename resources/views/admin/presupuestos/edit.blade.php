@php($order = $quote->workOrder)
<x-layouts.app :title="'Presupuesto v'.$quote->version.' · '.$order->code">
    <x-slot:actions>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.trabajos.show', $order) }}" class="btn">Volver al trabajo</a>
            <a href="{{ route('admin.presupuestos.pdf', $quote) }}" class="btn">PDF interno</a>
        </div>
    </x-slot:actions>
    <p class="-mt-4 mb-6 text-sm text-stone-600">{{ $order->title }} · {{ $order->client->name }} · <span class="badge {{ $quote->status->badgeClasses() }}">{{ $quote->status->label() }}</span></p>
    @error('quote')<div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>@enderror

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="card lg:col-span-2">
            <h2 class="mb-3 font-semibold">Ítems</h2>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Descripción</th><th class="!text-right">Cant.</th><th class="!text-right">Precio unit.</th><th class="!text-right">Subtotal</th><th></th></tr></thead>
                    <tbody class="divide-y divide-stone-100">
                        @forelse ($quote->items as $item)
                            <tr>
                                <td>{{ $item->description }} @unless ($item->material_id)<span class="badge">libre</span>@endunless</td>
                                <td class="text-right whitespace-nowrap">{{ qty($item->qty) }} {{ $item->unit }}</td>
                                <td class="text-right whitespace-nowrap">{{ money($item->unit_price) }}</td>
                                <td class="text-right whitespace-nowrap">{{ money($item->line_total) }}</td>
                                <td class="text-right">
                                    @if ($quote->isEditable())
                                        <form method="POST" action="{{ route('admin.presupuestos.items.destroy', [$quote, $item]) }}">@csrf @method('DELETE')<button class="text-xs text-red-700 underline">Quitar</button></form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-stone-500">Agregá insumos o ítems libres (flete, instalación, herrajes especiales).</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($quote->isEditable())
                <div class="mt-6 grid gap-6 md:grid-cols-2">
                    <form method="POST" action="{{ route('admin.presupuestos.items.store', $quote) }}" class="space-y-2 rounded-md border border-stone-200 p-3">
                        @csrf
                        <h3 class="text-sm font-semibold">Agregar insumo del stock</h3>
                        <select class="input" name="material_id" required aria-label="Insumo">
                            <option value="">Elegí un insumo</option>
                            @foreach ($materials->groupBy('category.name') as $category => $items)
                                <optgroup label="{{ $category }}">
                                    @foreach ($items as $m)<option value="{{ $m->id }}">{{ $m->name }} · {{ money($m->current_price) }}/{{ $m->unit->label() }}</option>@endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <input class="input" type="number" step="0.001" min="0.001" name="qty" placeholder="Cantidad" required aria-label="Cantidad">
                        <input class="input" name="description" placeholder="Descripción (opcional, por defecto el nombre)" aria-label="Descripción">
                        <button class="btn-primary w-full">Agregar</button>
                    </form>
                    <form method="POST" action="{{ route('admin.presupuestos.items.store', $quote) }}" class="space-y-2 rounded-md border border-stone-200 p-3">
                        @csrf
                        <h3 class="text-sm font-semibold">Agregar ítem libre</h3>
                        <input class="input" name="description" placeholder="Ej.: Flete e instalación" required aria-label="Descripción">
                        <div class="grid grid-cols-3 gap-2">
                            <input class="input" type="number" step="0.001" min="0.001" name="qty" value="1" required aria-label="Cantidad">
                            <input class="input" name="unit" value="unidad" required aria-label="Unidad">
                            <input class="input" type="number" step="0.01" min="0" name="unit_price" placeholder="Precio" required aria-label="Precio unitario">
                        </div>
                        <button class="btn w-full">Agregar</button>
                    </form>
                </div>
                @if ($errors->hasAny(['material_id', 'qty', 'description', 'unit_price']))
                    <p class="error">{{ $errors->first('material_id') ?: $errors->first('qty') ?: $errors->first('description') ?: $errors->first('unit_price') }}</p>
                @endif
            @endif
        </section>

        <aside class="space-y-6">
            <form method="POST" action="{{ route('admin.presupuestos.update', $quote) }}" class="card space-y-3">
                @csrf @method('PUT')
                <h2 class="font-semibold">Totales</h2>
                <fieldset @disabled(! $quote->isEditable()) class="space-y-3">
                    <div><label class="label" for="labor_cost">Mano de obra ($)</label><input class="input" id="labor_cost" type="number" step="0.01" min="0" name="labor_cost" value="{{ old('labor_cost', $quote->labor_cost) }}"></div>
                    <div class="grid grid-cols-2 gap-2">
                        <div><label class="label" for="margin_pct">Margen (%)</label><input class="input" id="margin_pct" type="number" step="0.01" min="0" name="margin_pct" value="{{ old('margin_pct', $quote->margin_pct) }}"></div>
                        <div><label class="label" for="tax_pct">IVA (%)</label><input class="input" id="tax_pct" type="number" step="0.01" min="0" name="tax_pct" value="{{ old('tax_pct', $quote->tax_pct) }}"></div>
                    </div>
                    <div><label class="label" for="valid_until">Válido hasta</label><input class="input" id="valid_until" type="date" name="valid_until" value="{{ old('valid_until', $quote->valid_until?->toDateString()) }}"></div>
                    <div><label class="label" for="notes">Notas para el cliente</label><textarea class="input" id="notes" name="notes" rows="3" placeholder="Forma de pago, plazo de entrega, qué incluye">{{ old('notes', $quote->notes) }}</textarea></div>
                    @if ($quote->isEditable())<button class="btn w-full">Recalcular y guardar</button>@endif
                </fieldset>
                <dl class="space-y-1 border-t border-stone-200 pt-3 text-sm">
                    <div class="flex justify-between"><dt class="text-stone-500">Materiales e ítems</dt><dd>{{ money($quote->itemsTotal()) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-stone-500">Mano de obra</dt><dd>{{ money($quote->labor_cost) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-stone-500">Subtotal con margen ({{ qty($quote->margin_pct) }}%)</dt><dd>{{ money($quote->subtotal) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-stone-500">IVA ({{ qty($quote->tax_pct) }}%)</dt><dd>{{ money($quote->taxAmount()) }}</dd></div>
                    <div class="flex justify-between text-base font-semibold"><dt>Total</dt><dd>{{ money($quote->total) }}</dd></div>
                </dl>
            </form>

            @if ($quote->isEditable())
                <form method="POST" action="{{ route('admin.presupuestos.send', $quote) }}" class="card" onsubmit="return confirm('¿Enviar el presupuesto al cliente? Después no se puede editar.')">
                    @csrf
                    <p class="mb-3 text-sm text-stone-600">El cliente lo ve en su cuenta sin precios unitarios ni margen: solo ítems, subtotal, IVA y total.</p>
                    <button class="btn-primary w-full">Enviar al cliente</button>
                </form>
                <form method="POST" action="{{ route('admin.presupuestos.destroy', $quote) }}" onsubmit="return confirm('¿Borrar este borrador?')">@csrf @method('DELETE')<button class="btn-danger w-full">Borrar borrador</button></form>
            @endif
        </aside>
    </div>
</x-layouts.app>
