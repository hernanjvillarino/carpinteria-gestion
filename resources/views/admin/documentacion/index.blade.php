<x-layouts.app title="Tipos de documento">
    <x-slot:actions><a href="{{ route('admin.personal.index') }}" class="btn">Volver al personal</a></x-slot:actions>
    <p class="mb-4 max-w-3xl text-sm text-stone-600">
        Los documentos que te pueden pedir los barrios para dejar entrar al personal. Los generales (ART, accidentes personales, DNI) se tildan
        en cada barrio. Si un barrio tiene un permiso o credencial propia, cargalo eligiendo ese barrio: se le pide siempre a quien entre ahí.
    </p>

    <form method="POST" action="{{ route('admin.documentacion.store') }}" class="card mb-6 flex max-w-4xl flex-wrap items-end gap-3">
        @csrf
        <div class="min-w-64 flex-1">
            <label class="label" for="name">Nuevo documento</label>
            <input class="input" id="name" name="name" maxlength="150" required placeholder="Ej.: Seguro de accidentes personales">
            @error('name')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="gated_community_id">¿Es propio de un barrio?</label>
            <select class="input" id="gated_community_id" name="gated_community_id">
                <option value="">No, es general</option>
                @foreach ($barrios as $barrio)<option value="{{ $barrio->id }}">Sí, de {{ $barrio->name }}</option>@endforeach
            </select>
        </div>
        <label class="flex items-center gap-2 pb-2 text-sm"><input type="checkbox" name="requires_expiry" value="1" checked> Vence</label>
        <button class="btn-primary">Agregar</button>
    </form>

    <div class="card max-w-4xl !p-0">
        <ul class="divide-y divide-stone-100">
            @forelse ($types as $type)
                <li class="flex flex-wrap items-center gap-3 px-4 py-2">
                    <form method="POST" action="{{ route('admin.documentacion.update', $type) }}" class="flex flex-1 flex-wrap items-center gap-3">
                        @csrf @method('PUT')
                        <input class="input min-w-48 flex-1" name="name" value="{{ $type->name }}" maxlength="150" aria-label="Nombre">
                        <select class="input !w-auto" name="gated_community_id" aria-label="Barrio">
                            <option value="">General</option>
                            @foreach ($barrios as $barrio)<option value="{{ $barrio->id }}" @selected($type->gated_community_id === $barrio->id)>De {{ $barrio->name }}</option>@endforeach
                        </select>
                        <label class="flex items-center gap-1 text-sm"><input type="checkbox" name="requires_expiry" value="1" @checked($type->requires_expiry)> Vence</label>
                        <span class="text-xs text-stone-500">{{ $type->gated_community_id ? '' : 'Lo piden '.$type->required_by_count.' barrio(s) · ' }}{{ $type->documents_count }} archivo(s)</span>
                        <button class="btn">Guardar</button>
                    </form>
                    <form method="POST" action="{{ route('admin.documentacion.destroy', $type) }}" onsubmit="return confirm('¿Eliminar este documento? Se borran también los {{ $type->documents_count }} archivo(s) cargados.')">
                        @csrf @method('DELETE')
                        <button class="text-sm text-stone-400 hover:text-red-700">Eliminar</button>
                    </form>
                </li>
            @empty
                <li class="px-4 py-3 text-sm text-stone-500">Todavía no cargaste documentos.</li>
            @endforelse
        </ul>
    </div>
</x-layouts.app>
