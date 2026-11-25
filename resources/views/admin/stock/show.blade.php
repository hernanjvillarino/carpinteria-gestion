<x-layouts.app :title="$material->name">
    <x-slot:actions><a href="{{ route('admin.stock.edit', $material) }}" class="btn">Editar</a></x-slot:actions>
    <div class="grid gap-6 lg:grid-cols-3">
        <section class="card space-y-2 text-sm">
            <p class="text-3xl font-semibold {{ $material->isBelowMinimum() ? 'text-red-700' : 'text-stone-900' }}">{{ qty($material->stock_qty) }} <span class="text-base font-normal text-stone-500">{{ $material->unit->label() }}</span></p>
            @if ($material->isBelowMinimum())<p class="text-red-700">Por debajo del mínimo ({{ qty($material->min_stock) }}).</p>@else<p class="text-stone-500">Mínimo: {{ qty($material->min_stock) }}</p>@endif
            <p><span class="text-stone-500">Precio actual:</span> {{ money($material->current_price) }} por {{ $material->unit->label() }}</p>
            <p><span class="text-stone-500">Categoría:</span> {{ $material->category->name }}</p>
            <p><span class="text-stone-500">Proveedor:</span> {{ $material->supplier?->name ?? '—' }}</p>
            @if ($material->corteopt_material)
                <p><span class="text-stone-500">Placa:</span> {{ $material->corteopt_material->label() }} {{ qty($material->thickness_mm) }} mm · {{ $material->color?->name }}{{ $material->characteristic ? ' · '.$material->characteristic : '' }}{{ $material->has_grain ? ' · con veta' : '' }}</p>
            @endif
        </section>

        <section class="card lg:col-span-2">
            <h2 class="mb-3 font-semibold">Registrar movimiento</h2>
            <form method="POST" action="{{ route('admin.stock.move', $material) }}" class="grid gap-3 sm:grid-cols-2">
                @csrf
                <div>
                    <label class="label" for="type">Tipo</label>
                    <select class="input" id="type" name="type">
                        @foreach ($types as $t)<option value="{{ $t->value }}" @selected(old('type') === $t->value)>{{ $t->label() }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="qty">Cantidad ({{ $material->unit->label() }})</label>
                    <input class="input" id="qty" type="number" step="0.001" name="qty" value="{{ old('qty') }}" required>
                    <p class="mt-1 text-xs text-stone-500">En ajustes, usá negativo para descontar.</p>
                    @error('qty')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="unit_cost">Costo unitario (ingresos)</label>
                    <input class="input" id="unit_cost" type="number" step="0.01" min="0" name="unit_cost" value="{{ old('unit_cost') }}">
                    <label class="mt-1 flex items-center gap-2 text-xs"><input type="checkbox" name="update_price" value="1"> Actualizar el precio del insumo con este costo</label>
                </div>
                <div>
                    <label class="label" for="work_order_id">Trabajo (consumos)</label>
                    <select class="input" id="work_order_id" name="work_order_id">
                        <option value="">Ninguno</option>
                        @foreach ($orders as $o)<option value="{{ $o->id }}">{{ $o->code }} · {{ $o->title }}</option>@endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="label" for="note">Nota</label>
                    <input class="input" id="note" name="note" value="{{ old('note') }}" placeholder="Remito, factura, motivo del ajuste">
                </div>
                <div><button class="btn-primary">Registrar</button></div>
            </form>
        </section>

        <section class="card lg:col-span-2">
            <h2 class="mb-3 font-semibold">Movimientos</h2>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Fecha</th><th>Tipo</th><th class="!text-right">Cantidad</th><th>Trabajo</th><th>Quién</th><th>Nota</th></tr></thead>
                    <tbody class="divide-y divide-stone-100">
                        @forelse ($material->movements as $mv)
                            <tr>
                                <td class="whitespace-nowrap">{{ $mv->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $mv->type->label() }}</td>
                                <td class="text-right {{ $mv->qty < 0 ? 'text-red-700' : 'text-green-700' }}">{{ $mv->qty > 0 ? '+' : '' }}{{ qty($mv->qty) }}</td>
                                <td>@if ($mv->workOrder)<a class="underline" href="{{ route('admin.trabajos.show', $mv->workOrder) }}">{{ $mv->workOrder->code }}</a>@endif</td>
                                <td>{{ $mv->user->name }}</td>
                                <td>{{ $mv->note }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-stone-500">Sin movimientos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <h2 class="mb-3 font-semibold">Historial de precios</h2>
            @foreach ($material->prices as $p)
                <div class="flex justify-between border-b border-stone-100 py-1 text-sm last:border-0">
                    <span>{{ $p->valid_from->format('d/m/Y') }}</span><span>{{ money($p->price) }}</span>
                </div>
            @endforeach
        </section>
    </div>
</x-layouts.app>
