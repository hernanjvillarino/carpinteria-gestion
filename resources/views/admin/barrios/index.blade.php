<x-layouts.app title="Barrios cerrados">
    <x-slot:actions><a href="{{ route('admin.barrios.create') }}" class="btn-primary">Nuevo barrio</a></x-slot:actions>
    <div class="card overflow-x-auto !p-0">
        <table class="table">
            <thead><tr><th>Barrio</th><th>Localidad</th><th>Clientes</th><th>En el sitio</th><th></th></tr></thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($barrios as $barrio)
                    <tr>
                        <td class="font-medium">{{ $barrio->name }}</td>
                        <td>{{ $barrio->city }}</td>
                        <td>{{ $barrio->clients_count }}</td>
                        <td>{{ $barrio->show_on_website ? 'Sí' : 'No' }}</td>
                        <td class="text-right"><a href="{{ route('admin.barrios.edit', $barrio) }}" class="text-madera-700 underline">Editar</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-stone-500">Todavía no hay barrios cargados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $barrios->links() }}</div>
</x-layouts.app>
