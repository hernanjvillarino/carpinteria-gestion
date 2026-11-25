<x-layouts.app :title="$cliente->name">
    <x-slot:actions>
        <div class="flex gap-2">
            <a href="{{ route('admin.trabajos.create', ['client_id' => $cliente->id]) }}" class="btn-primary">Nuevo trabajo</a>
            <a href="{{ route('admin.clientes.edit', $cliente) }}" class="btn">Editar</a>
        </div>
    </x-slot:actions>
    <div class="grid gap-6 lg:grid-cols-3">
        <section class="card space-y-2 text-sm">
            <p><span class="text-stone-500">Ubicación:</span> {{ $cliente->locationLabel() ?: 'Sin cargar' }}</p>
            @if ($cliente->gatedCommunity && $cliente->address)<p><span class="text-stone-500">Dirección:</span> {{ $cliente->address }}</p>@endif
            <p><span class="text-stone-500">Teléfono:</span> {{ $cliente->phone ?: '—' }}</p>
            <p><span class="text-stone-500">Email:</span> {{ $cliente->email ?: '—' }}</p>
            <p><span class="text-stone-500">CUIT / DNI:</span> {{ $cliente->tax_id ?: '—' }}</p>
            <p><span class="text-stone-500">Portal:</span> {{ $cliente->user ? 'Con acceso' : 'Sin acceso' }}</p>
            @if ($cliente->gatedCommunity?->access_notes)
                <p class="rounded bg-amber-50 p-2 text-amber-900"><span class="font-medium">Ingreso al barrio:</span> {{ $cliente->gatedCommunity->access_notes }}</p>
            @endif
            @if ($cliente->notes)<p class="whitespace-pre-line">{{ $cliente->notes }}</p>@endif
        </section>
        <section class="card lg:col-span-2">
            <h2 class="mb-3 font-semibold">Trabajos</h2>
            @forelse ($cliente->workOrders as $order)
                <a href="{{ route('admin.trabajos.show', $order) }}" class="flex justify-between border-b border-stone-100 py-2 text-sm last:border-0">
                    <span>{{ $order->code }} · {{ $order->title }}</span><span class="badge">{{ $order->stage->name }}</span>
                </a>
            @empty
                <p class="text-sm text-stone-500">Todavía no tiene trabajos.</p>
            @endforelse
        </section>
    </div>
    <form method="POST" action="{{ route('admin.clientes.destroy', $cliente) }}" class="mt-6" onsubmit="return confirm('¿Archivar este cliente?')">
        @csrf @method('DELETE')
        <button class="btn-danger">Archivar cliente</button>
    </form>
</x-layouts.app>
