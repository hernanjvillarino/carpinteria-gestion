<x-layouts.app :title="$cliente->exists ? 'Editar cliente' : 'Nuevo cliente'">
    <form method="POST" action="{{ $cliente->exists ? route('admin.clientes.update', $cliente) : route('admin.clientes.store') }}" class="card max-w-3xl space-y-4">
        @csrf
        @if ($cliente->exists) @method('PUT') @endif
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="label" for="name">Nombre y apellido</label>
                <input class="input" id="name" name="name" value="{{ old('name', $cliente->name) }}" required>
                @error('name')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="email">Email</label>
                <input class="input" id="email" type="email" name="email" value="{{ old('email', $cliente->email) }}">
                @error('email')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="phone">Teléfono / WhatsApp</label>
                <input class="input" id="phone" name="phone" value="{{ old('phone', $cliente->phone) }}">
            </div>
            <div>
                <label class="label" for="tax_id">CUIT / DNI</label>
                <input class="input" id="tax_id" name="tax_id" value="{{ old('tax_id', $cliente->tax_id) }}">
            </div>
            <div>
                <label class="label" for="gated_community_id">Barrio cerrado</label>
                <select class="input" id="gated_community_id" name="gated_community_id">
                    <option value="">Ninguno</option>
                    @foreach ($barrios as $barrio)
                        <option value="{{ $barrio->id }}" @selected(old('gated_community_id', $cliente->gated_community_id) == $barrio->id)>{{ $barrio->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="lot">Lote / unidad</label>
                <input class="input" id="lot" name="lot" value="{{ old('lot', $cliente->lot) }}">
            </div>
            <div>
                <label class="label" for="address">Dirección</label>
                <input class="input" id="address" name="address" value="{{ old('address', $cliente->address) }}">
            </div>
            <div class="sm:col-span-2">
                <label class="label" for="notes">Notas</label>
                <textarea class="input" id="notes" name="notes" rows="3">{{ old('notes', $cliente->notes) }}</textarea>
            </div>
        </div>
        @unless ($cliente->user_id)
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="portal_access" value="1" @checked(old('portal_access'))> Darle acceso al portal de seguimiento (se genera una contraseña)</label>
        @else
            <p class="text-sm text-stone-600">Este cliente ya tiene acceso al portal con {{ $cliente->user->email }}.</p>
        @endunless
        <div class="flex gap-3"><button class="btn-primary">Guardar</button><a href="{{ $cliente->exists ? route('admin.clientes.show', $cliente) : route('admin.clientes.index') }}" class="btn">Cancelar</a></div>
    </form>
</x-layouts.app>
