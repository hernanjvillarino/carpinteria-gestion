<x-layouts.app title="Inicio">
    <x-slot:actions><a href="{{ route('admin.trabajos.create') }}" class="btn-primary">Nuevo trabajo</a></x-slot:actions>

    <section class="mb-8">
        <h2 class="mb-3 text-sm font-semibold tracking-wide text-stone-500 uppercase">Trabajos por etapa</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
            @foreach ($stages as $stage)
                <a href="{{ route('admin.trabajos.index', ['stage_id' => $stage->id, 'estado' => 'todos']) }}" class="card !p-3 hover:border-madera-600">
                    <div class="text-2xl font-semibold text-stone-900">{{ $stage->work_orders_count }}</div>
                    <div class="text-sm text-stone-600">{{ $stage->name }}</div>
                </a>
            @endforeach
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="card">
            <h2 class="mb-3 font-semibold">Atrasados</h2>
            @forelse ($overdue as $order)
                <a href="{{ route('admin.trabajos.show', $order) }}" class="flex justify-between border-b border-stone-100 py-2 text-sm last:border-0">
                    <span>{{ $order->code }} · {{ $order->title }} <span class="text-stone-500">({{ $order->client->name }})</span></span>
                    <span class="text-red-700">{{ $order->estimated_delivery->format('d/m/Y') }}</span>
                </a>
            @empty
                <p class="text-sm text-stone-500">No hay trabajos atrasados.</p>
            @endforelse

            <h2 class="mt-6 mb-3 font-semibold">Respuestas de clientes a diseños (14 días)</h2>
            @forelse ($designResponses as $design)
                <a href="{{ route('admin.trabajos.show', $design->workOrder) }}" class="flex justify-between border-b border-stone-100 py-2 text-sm last:border-0">
                    <span>{{ $design->workOrder->code }} · {{ $design->workOrder->client->name }} · v{{ $design->version }}</span>
                    <span class="badge {{ $design->status->badgeClasses() }}">{{ $design->status->label() }}</span>
                </a>
            @empty
                <p class="text-sm text-stone-500">Sin respuestas recientes.</p>
            @endforelse

            <h2 class="mt-6 mb-3 font-semibold">Sin carpintero asignado</h2>
            @forelse ($unassigned as $order)
                <a href="{{ route('admin.trabajos.edit', $order) }}" class="block border-b border-stone-100 py-2 text-sm last:border-0">{{ $order->code }} · {{ $order->title }} <span class="text-stone-500">({{ $order->client->name }})</span></a>
            @empty
                <p class="text-sm text-stone-500">Todos los trabajos activos tienen carpintero.</p>
            @endforelse
        </section>

        <section class="card">
            <h2 class="mb-3 font-semibold">Últimos avances</h2>
            @forelse ($recentUpdates as $update)
                <a href="{{ route('admin.trabajos.show', $update->workOrder) }}" class="block border-b border-stone-100 py-2 text-sm last:border-0">
                    <span class="font-medium">{{ $update->workOrder->code }}</span>
                    · {{ $update->user->name }}
                    @if ($update->toStage) pasó a <span class="badge">{{ $update->toStage->name }}</span> @endif
                    @if ($update->photos_count) · {{ $update->photos_count }} foto(s) @endif
                    <span class="block text-xs text-stone-500">{{ $update->created_at->diffForHumans() }}</span>
                </a>
            @empty
                <p class="text-sm text-stone-500">Todavía no hay avances.</p>
            @endforelse
        </section>
    </div>
</x-layouts.app>
