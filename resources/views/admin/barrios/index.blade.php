<x-layouts.app title="Barrios cerrados">
    <x-slot:actions>
        <a href="{{ route('admin.personal.index') }}" class="btn">Personal y documentación</a>
        <a href="{{ route('admin.barrios.create') }}" class="btn-primary">Nuevo barrio</a>
    </x-slot:actions>
    <div class="card overflow-x-auto !p-0">
        <table class="table">
            <thead><tr><th>Barrio</th><th>Localidad</th><th>Clientes</th><th>Documentos que pide</th><th>Personal habilitado</th><th>En el sitio</th><th></th></tr></thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($barrios as $barrio)
                    @php($required = $barrio->requiredTypes())
                    <tr>
                        <td class="font-medium"><a href="{{ route('admin.barrios.show', $barrio) }}" class="hover:underline">{{ $barrio->name }}</a></td>
                        <td>{{ $barrio->city }}</td>
                        <td>{{ $barrio->clients_count }}</td>
                        <td class="text-stone-600">{{ $required->isEmpty() ? 'Sin cargar' : $required->pluck('name')->join(', ') }}</td>
                        <td>
                            @if ($required->isNotEmpty())
                                @php($ok = $enabledCount[$barrio->id])
                                <span @class(['badge', '!bg-green-100 !text-green-800' => $ok === $employees->count() && $ok > 0, '!bg-amber-100 !text-amber-800' => $ok < $employees->count()])>{{ $ok }} de {{ $employees->count() }}</span>
                            @else
                                <span class="text-stone-400">—</span>
                            @endif
                        </td>
                        <td>{{ $barrio->show_on_website ? 'Sí' : 'No' }}</td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('admin.barrios.show', $barrio) }}" class="text-madera-700 underline">Ver</a>
                            <a href="{{ route('admin.barrios.edit', $barrio) }}" class="ml-3 text-madera-700 underline">Editar</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-stone-500">Todavía no hay barrios cargados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $barrios->links() }}</div>
</x-layouts.app>
