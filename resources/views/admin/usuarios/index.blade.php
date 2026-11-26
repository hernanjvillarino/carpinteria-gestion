<x-layouts.app title="Equipo">
    <x-slot:actions><a href="{{ route('admin.indicaciones.index') }}" class="btn">Indicaciones precargadas</a> <a href="{{ route('admin.usuarios.create') }}" class="btn-primary">Nuevo usuario</a></x-slot:actions>
    <p class="mb-4 text-sm text-stone-600">Administradores y carpinteros. Los accesos de clientes se dan desde la ficha de cada cliente.</p>
    <div class="card overflow-x-auto !p-0">
        <table class="table">
            <thead><tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Trabajos asignados</th><th>Estado</th><th></th></tr></thead>
            <tbody class="divide-y divide-stone-100">
                @foreach ($usuarios as $usuario)
                    <tr>
                        <td class="font-medium">{{ $usuario->name }}</td>
                        <td>{{ $usuario->email }}</td>
                        <td>{{ $usuario->role->label() }}</td>
                        <td>{{ $usuario->assigned_work_orders_count }}</td>
                        <td>{!! $usuario->is_active ? 'Activo' : '<span class="text-red-700">Inactivo</span>' !!}</td>
                        <td class="text-right"><a href="{{ route('admin.usuarios.edit', $usuario) }}" class="text-madera-700 underline">Editar</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.app>
