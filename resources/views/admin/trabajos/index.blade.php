<x-layouts.app title="Trabajos">
    <x-slot:actions><a href="{{ route('admin.trabajos.create') }}" class="btn-primary">Nuevo trabajo</a></x-slot:actions>
    <form class="mb-4 grid gap-2 sm:grid-cols-5">
        <input class="input" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Código, título o cliente">
        <select class="input" name="stage_id">
            <option value="">Todas las etapas</option>
            @foreach ($stages as $stage)<option value="{{ $stage->id }}" @selected(($filters['stage_id'] ?? null) == $stage->id)>{{ $stage->name }}</option>@endforeach
        </select>
        <select class="input" name="carpintero_id">
            <option value="">Todos los carpinteros</option>
            @foreach ($carpinteros as $c)<option value="{{ $c->id }}" @selected(($filters['carpintero_id'] ?? null) == $c->id)>{{ $c->name }}</option>@endforeach
        </select>
        <select class="input" name="estado">
            <option value="activos" @selected($filters['estado'] === 'activos')>Activos</option>
            <option value="entregados" @selected($filters['estado'] === 'entregados')>Entregados</option>
            <option value="todos" @selected($filters['estado'] === 'todos')>Todos</option>
        </select>
        <button class="btn">Filtrar</button>
    </form>
    <div class="card overflow-x-auto !p-0">
        <table class="table">
            <thead><tr><th>Código</th><th>Trabajo</th><th>Cliente</th><th>Etapa</th><th>Carpinteros</th><th>Entrega</th></tr></thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($orders as $order)
                    <tr>
                        <td><a href="{{ route('admin.trabajos.show', $order) }}" class="font-medium text-madera-700 underline">{{ $order->code }}</a></td>
                        <td>{{ $order->title }} @if ($order->priority === \App\Enums\Priority::Alta)<span class="badge !bg-red-100 !text-red-800">Alta</span>@endif</td>
                        <td>{{ $order->client->name }}<br><span class="text-stone-500">{{ $order->client->locationLabel() }}</span></td>
                        <td><span class="badge">{{ $order->stage->name }}</span></td>
                        <td>{{ $order->carpenters->pluck('name')->join(', ') ?: '—' }}</td>
                        <td @class(['text-red-700' => ! $order->delivered_at && $order->estimated_delivery?->isPast()])>{{ $order->estimated_delivery?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-stone-500">No hay trabajos con esos filtros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $orders->links() }}</div>
</x-layouts.app>
