<x-layouts.app :title="$employee->exists ? 'Editar '.$employee->name : 'Nuevo empleado'">
    <form method="POST" action="{{ $employee->exists ? route('admin.personal.update', $employee) : route('admin.personal.store') }}" class="card max-w-2xl space-y-4">
        @csrf
        @if ($employee->exists) @method('PUT') @endif
        <div>
            <label class="label" for="name">Nombre y apellido</label>
            <input class="input" id="name" name="name" value="{{ old('name', $employee->name) }}" required maxlength="150">
            @error('name')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="label" for="dni">DNI</label>
                <input class="input" id="dni" name="dni" value="{{ old('dni', $employee->dni) }}" maxlength="20" inputmode="numeric">
            </div>
            <div>
                <label class="label" for="cuil">CUIL</label>
                <input class="input" id="cuil" name="cuil" value="{{ old('cuil', $employee->cuil) }}" maxlength="20">
            </div>
            <div>
                <label class="label" for="phone">Teléfono</label>
                <input class="input" id="phone" name="phone" value="{{ old('phone', $employee->phone) }}" maxlength="40">
            </div>
        </div>
        <div>
            <label class="label" for="user_id">Acceso al sistema</label>
            <select class="input" id="user_id" name="user_id">
                <option value="">Sin acceso (ayudante, instalador externo...)</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(old('user_id', $employee->user_id) == $user->id)>{{ $user->name }} · {{ $user->role->label() }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-stone-500">Vincularlo sirve para avisarte si asignás a un trabajo a alguien que no puede entrar a ese barrio.</p>
            @error('user_id')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="notes">Notas</label>
            <textarea class="input" id="notes" name="notes" rows="2">{{ old('notes', $employee->notes) }}</textarea>
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $employee->is_active))> Activo (los inactivos no figuran en los barrios ni reciben avisos de vencimiento)</label>
        <div class="flex gap-3">
            <button class="btn-primary">Guardar</button>
            <a href="{{ $employee->exists ? route('admin.personal.show', $employee) : route('admin.personal.index') }}" class="btn">Cancelar</a>
        </div>
    </form>
    @if ($employee->exists)
        <form method="POST" action="{{ route('admin.personal.destroy', $employee) }}" class="mt-4" onsubmit="return confirm('¿Eliminar este empleado y todos sus documentos? Si dejó de trabajar, mejor marcalo como inactivo.')">
            @csrf @method('DELETE')
            <button class="btn-danger">Eliminar empleado</button>
        </form>
    @endif
</x-layouts.app>
