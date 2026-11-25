<x-layouts.app title="Stock de insumos">
    <x-slot:actions>
        <div class="flex gap-2">
            <a href="{{ route('admin.proveedores.index') }}" class="btn">Proveedores</a>
            <a href="{{ route('admin.stock.create') }}" class="btn-primary">Nuevo insumo</a>
        </div>
    </x-slot:actions>
    <form class="mb-4 flex flex-wrap items-center gap-2">
        <input class="input max-w-xs" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Buscar por nombre o código">
        <select class="input max-w-xs" name="categoria">
            <option value="">Todas las categorías</option>
            @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(($filters['categoria'] ?? null) == $c->id)>{{ $c->name }}</option>@endforeach
        </select>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="bajo_minimo" value="1" @checked($filters['bajo_minimo'] ?? false)> Solo bajo mínimo</label>
        <button class="btn">Filtrar</button>
    </form>
    <div class="card overflow-x-auto !p-0">
        <table class="table">
            <thead><tr><th>Insumo</th><th>Categoría</th><th class="!text-right">Stock</th><th class="!text-right">Mínimo</th><th class="!text-right">Precio</th><th>Proveedor</th></tr></thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($materials as $m)
                    <tr @class(['bg-red-50' => $m->is_active && $m->isBelowMinimum(), 'text-stone-400' => ! $m->is_active])>
                        <td><a href="{{ route('admin.stock.show', $m) }}" class="font-medium text-madera-700 underline">{{ $m->name }}</a>
                            @if ($m->sku)<span class="text-xs text-stone-500">{{ $m->sku }}</span>@endif
                            @unless ($m->is_active)<span class="badge">Inactivo</span>@endunless
                        </td>
                        <td>{{ $m->category->name }}</td>
                        <td class="text-right">{{ qty($m->stock_qty) }} {{ $m->unit->label() }}</td>
                        <td class="text-right">{{ qty($m->min_stock) }}</td>
                        <td class="text-right whitespace-nowrap">{{ money($m->current_price) }}</td>
                        <td>{{ $m->supplier?->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-stone-500">No hay insumos con esos filtros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $materials->links() }}</div>

    <div class="mt-8 grid gap-6 sm:grid-cols-2">
        <form method="POST" action="{{ route('admin.stock.categorias.store') }}" class="card flex items-end gap-2">
            @csrf
            <div class="flex-1"><label class="label" for="cat_name">Nueva categoría</label><input class="input" id="cat_name" name="name" required></div>
            <button class="btn">Agregar</button>
        </form>
        <form method="POST" action="{{ route('admin.stock.colores.store') }}" class="card flex items-end gap-2">
            @csrf
            <div class="flex-1"><label class="label" for="color_name">Nuevo color de placa</label><input class="input" id="color_name" name="name" placeholder="Escribilo siempre igual" required></div>
            <button class="btn">Agregar</button>
        </form>
    </div>
    @error('name')<p class="error mt-2">{{ $message }}</p>@enderror
</x-layouts.app>
