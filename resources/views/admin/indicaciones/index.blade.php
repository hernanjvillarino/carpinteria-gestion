<x-layouts.app title="Indicaciones precargadas">
    <p class="mb-4 max-w-3xl text-sm text-stone-600">
        Armá una sola vez las indicaciones que solés darle a los carpinteros. Al crear o editar un trabajo, las tildás y les llegan como tareas
        para ir marcando. Las que desactives dejan de aparecer para tildar, pero no se borran de los trabajos donde ya se usaron.
    </p>

    <form method="POST" action="{{ route('admin.indicaciones.store') }}" class="card mb-6 flex max-w-3xl flex-wrap items-end gap-3">
        @csrf
        <div class="min-w-64 flex-1">
            <label class="label" for="text">Nueva indicación</label>
            <input class="input" id="text" name="text" maxlength="255" required placeholder="Ej.: Cantos en ABS de 2 mm en los frentes">
            @error('text')<p class="error">{{ $message }}</p>@enderror
        </div>
        <button class="btn-primary">Agregar</button>
    </form>

    <div class="card max-w-3xl !p-0">
        <ul class="divide-y divide-stone-100">
            @forelse ($templates as $template)
                <li class="flex flex-wrap items-center gap-3 px-4 py-2">
                    <form method="POST" action="{{ route('admin.indicaciones.update', $template) }}" class="flex flex-1 flex-wrap items-center gap-3">
                        @csrf @method('PUT')
                        <input class="input !w-16" type="number" name="position" value="{{ $template->position }}" min="0" max="999" title="Orden" aria-label="Orden">
                        <input class="input min-w-48 flex-1" name="text" value="{{ $template->text }}" maxlength="255" aria-label="Texto">
                        <label class="flex items-center gap-1 text-sm"><input type="checkbox" name="is_active" value="1" @checked($template->is_active)> Activa</label>
                        <button class="btn">Guardar</button>
                    </form>
                    <form method="POST" action="{{ route('admin.indicaciones.destroy', $template) }}">
                        @csrf @method('DELETE')
                        <button class="text-sm text-stone-400 hover:text-red-700">Eliminar</button>
                    </form>
                </li>
            @empty
                <li class="px-4 py-3 text-sm text-stone-500">Todavía no cargaste indicaciones.</li>
            @endforelse
        </ul>
    </div>
</x-layouts.app>
