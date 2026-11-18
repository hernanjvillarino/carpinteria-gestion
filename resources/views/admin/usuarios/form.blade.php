<x-layouts.app :title="$usuario->exists ? 'Editar usuario' : 'Nuevo usuario'">
    <form method="POST" action="{{ $usuario->exists ? route('admin.usuarios.update', $usuario) : route('admin.usuarios.store') }}" class="card max-w-2xl space-y-4">
        @csrf
        @if ($usuario->exists) @method('PUT') @endif
        <div>
            <label class="label" for="name">Nombre</label>
            <input class="input" id="name" name="name" value="{{ old('name', $usuario->name) }}" required>
            @error('name')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="email">Email (usuario de ingreso)</label>
                <input class="input" id="email" type="email" name="email" value="{{ old('email', $usuario->email) }}" required>
                @error('email')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="phone">Teléfono</label>
                <input class="input" id="phone" name="phone" value="{{ old('phone', $usuario->phone) }}">
            </div>
            <div>
                <label class="label" for="role">Rol</label>
                <select class="input" id="role" name="role">
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected(old('role', $usuario->role?->value) === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
                @error('role')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="password">{{ $usuario->exists ? 'Nueva contraseña (opcional)' : 'Contraseña' }}</label>
                <input class="input" id="password" type="password" name="password" autocomplete="new-password" @required(! $usuario->exists)>
                @error('password')<p class="error">{{ $message }}</p>@enderror
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $usuario->is_active))> Activo (puede ingresar)</label>
        <div class="flex gap-3"><button class="btn-primary">Guardar</button><a href="{{ route('admin.usuarios.index') }}" class="btn">Cancelar</a></div>
    </form>
</x-layouts.app>
