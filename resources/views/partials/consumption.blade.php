{{-- Materiales usados en el trabajo (admin y carpintero asignado). --}}
<section class="card">
    <h2 class="mb-3 font-semibold">Materiales usados</h2>
    <form method="POST" action="{{ route('trabajos.consumos.store', $order) }}" class="mb-4 grid gap-2 sm:grid-cols-4">
        @csrf
        <select class="input sm:col-span-2" name="material_id" required aria-label="Material">
            <option value="">Elegí el material</option>
            @foreach ($materials as $m)<option value="{{ $m->id }}" @selected(old('material_id') == $m->id)>{{ $m->name }} ({{ qty($m->stock_qty) }} {{ $m->unit->label() }} en stock)</option>@endforeach
        </select>
        <input class="input" type="number" step="0.001" min="0.001" name="qty" placeholder="Cantidad" required aria-label="Cantidad">
        <button class="btn">Registrar consumo</button>
        <input class="input sm:col-span-4" name="note" placeholder="Nota (opcional)" aria-label="Nota">
    </form>
    @error('material_id')<p class="error">{{ $message }}</p>@enderror
    @error('qty')<p class="error">{{ $message }}</p>@enderror
    @forelse ($order->stockMovements as $mv)
        <div class="flex justify-between border-b border-stone-100 py-1 text-sm last:border-0">
            <span>{{ $mv->material->name }} · {{ qty(abs($mv->qty)) }} {{ $mv->material->unit->label() }} <span class="text-stone-500">({{ $mv->user->name }}, {{ $mv->created_at->format('d/m') }})</span></span>
            @if ($isAdmin && $mv->unit_cost)<span class="text-stone-500">{{ money(abs($mv->qty) * (float) $mv->unit_cost) }}</span>@endif
        </div>
    @empty
        <p class="text-sm text-stone-500">Sin consumos registrados.</p>
    @endforelse
</section>
