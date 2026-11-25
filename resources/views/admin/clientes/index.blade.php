<x-layouts.app title="Clientes">
    <x-slot:actions><a href="{{ route('admin.clientes.create') }}" class="btn-primary">Nuevo cliente</a></x-slot:actions>
    <form class="mb-4 flex gap-2"><input class="input max-w-xs" name="q" value="{{ $search }}" placeholder="Buscar por nombre, email o teléfono"><button class="btn">Buscar</button></form>
    <div class="card overflow-x-auto !p-0">
        <table class="table">
            <thead><tr><th>Cliente</th><th>Ubicación</th><th>Contacto</th><th>Trabajos</th></tr></thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($clientes as $cliente)
                    <tr>
                        <td><a href="{{ route('admin.clientes.show', $cliente) }}" class="font-medium text-madera-700 underline">{{ $cliente->name }}</a></td>
                        <td>{{ $cliente->locationLabel() }}</td>
                        <td>{{ $cliente->phone }}<br><span class="text-stone-500">{{ $cliente->email }}</span></td>
                        <td>{{ $cliente->work_orders_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-stone-500">No hay clientes{{ $search ? ' que coincidan con la búsqueda' : '' }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $clientes->links() }}</div>
</x-layouts.app>
