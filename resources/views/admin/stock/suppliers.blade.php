<x-layouts.app title="Proveedores">
    <div class="card mb-6">
        <form method="POST" action="{{ route('admin.proveedores.store') }}" class="grid gap-3 sm:grid-cols-4 sm:items-end">
            @csrf
            <div><label class="label" for="name">Nombre</label><input class="input" id="name" name="name" required></div>
            <div><label class="label" for="phone">Teléfono</label><input class="input" id="phone" name="phone"></div>
            <div><label class="label" for="email">Email</label><input class="input" id="email" type="email" name="email"></div>
            <div><button class="btn-primary">Agregar proveedor</button></div>
        </form>
        @error('name')<p class="error">{{ $message }}</p>@enderror
    </div>
    <div class="card space-y-3">
        @forelse ($suppliers as $s)
            <form method="POST" action="{{ route('admin.proveedores.update', $s) }}" class="grid gap-2 border-b border-stone-100 pb-3 last:border-0 sm:grid-cols-5 sm:items-center">
                @csrf @method('PUT')
                <input class="input" name="name" value="{{ $s->name }}" aria-label="Nombre" required>
                <input class="input" name="phone" value="{{ $s->phone }}" aria-label="Teléfono">
                <input class="input" name="email" value="{{ $s->email }}" aria-label="Email">
                <span class="text-sm text-stone-500">{{ $s->materials_count }} insumos</span>
                <button class="btn">Guardar</button>
            </form>
        @empty
            <p class="text-sm text-stone-500">Todavía no hay proveedores.</p>
        @endforelse
    </div>
</x-layouts.app>
