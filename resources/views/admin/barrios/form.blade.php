<x-layouts.app :title="$barrio->exists ? 'Editar barrio' : 'Nuevo barrio'">
    <form method="POST" action="{{ $barrio->exists ? route('admin.barrios.update', $barrio) : route('admin.barrios.store') }}" class="card max-w-2xl space-y-4">
        @csrf
        @if ($barrio->exists) @method('PUT') @endif
        <div>
            <label class="label" for="name">Nombre</label>
            <input class="input" id="name" name="name" value="{{ old('name', $barrio->name) }}" required>
            @error('name')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="city">Localidad</label>
            <input class="input" id="city" name="city" value="{{ old('city', $barrio->city) }}">
        </div>
        <div>
            <label class="label" for="access_notes">Requisitos de ingreso</label>
            <textarea class="input" id="access_notes" name="access_notes" rows="3" placeholder="ART, seguro, horarios permitidos, contacto de la administración">{{ old('access_notes', $barrio->access_notes) }}</textarea>
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="show_on_website" value="1" @checked(old('show_on_website', $barrio->show_on_website))> Mostrar en el sitio público como barrio donde trabajamos</label>
        <div class="flex gap-3"><button class="btn-primary">Guardar</button><a href="{{ route('admin.barrios.index') }}" class="btn">Cancelar</a></div>
    </form>
    @if ($barrio->exists)
        <form method="POST" action="{{ route('admin.barrios.destroy', $barrio) }}" class="mt-4" onsubmit="return confirm('¿Eliminar este barrio? Los clientes quedan sin barrio asignado.')">
            @csrf @method('DELETE')
            <button class="btn-danger">Eliminar barrio</button>
        </form>
    @endif
</x-layouts.app>
