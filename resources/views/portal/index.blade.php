<x-layouts.app title="Mis pedidos">
    <div class="space-y-4">
        @forelse ($orders as $order)
            @php($current = $order->stage->visible_to_client ? $order->stage : $stages->where('position', '<', $order->stage->position)->last())
            @php($done = $stages->where('position', '<=', $order->stage->position)->count())
            <a href="{{ route('portal.trabajos.show', $order) }}" class="card block hover:border-madera-600">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="font-semibold text-stone-900">{{ $order->title }} <span class="text-sm font-normal text-stone-500">{{ $order->code }}</span></h2>
                    @if ($order->pending_quotes_count)
                        <span class="badge !bg-amber-100 !text-amber-800">Tenés un presupuesto para revisar</span>
                    @endif
                    @if ($order->pending_designs_count)
                        <span class="badge !bg-amber-100 !text-amber-800">Tenés un diseño para aprobar</span>
                    @endif
                    @if ($order->delivered_at && ! $order->rating_exists)
                        <span class="badge !bg-emerald-100 !text-emerald-800">Calificá el trabajo</span>
                    @endif
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-stone-100" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $stages->count() }}" aria-valuenow="{{ $done }}">
                    <div class="h-full rounded-full bg-madera-600" style="width: {{ $stages->count() ? round($done / $stages->count() * 100) : 0 }}%"></div>
                </div>
                <p class="mt-2 text-sm text-stone-600">
                    Etapa actual: <span class="font-medium text-stone-900">{{ $current?->name ?? $order->stage->name }}</span>
                    @if ($order->delivered_at) · Entregado el {{ $order->delivered_at->format('d/m/Y') }}
                    @elseif ($order->estimated_delivery) · Entrega estimada {{ $order->estimated_delivery->format('d/m/Y') }}
                    @endif
                </p>
            </a>
        @empty
            <div class="card text-stone-600">Todavía no tenés pedidos cargados. Cuando empecemos tu trabajo lo vas a ver acá.</div>
        @endforelse
    </div>
</x-layouts.app>
