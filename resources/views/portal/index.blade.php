<x-layouts.app title="Mis pedidos">
    <div class="space-y-4">
        @forelse ($orders as $order)
            <div class="card">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="font-semibold">{{ $order->title }} <span class="text-sm font-normal text-stone-500">{{ $order->code }}</span></h2>
                    <span class="badge">{{ $order->stage->visible_to_client ? $order->stage->name : 'En producción' }}</span>
                </div>
                @if ($order->estimated_delivery)<p class="mt-1 text-sm text-stone-600">Entrega estimada: {{ $order->estimated_delivery->format('d/m/Y') }}</p>@endif
            </div>
        @empty
            <p class="text-stone-500">Todavía no tenés pedidos cargados.</p>
        @endforelse
        <p class="text-sm text-stone-500">Muy pronto vas a poder ver acá cada etapa de tu pedido con fotos.</p>
    </div>
</x-layouts.app>
