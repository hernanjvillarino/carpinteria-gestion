<x-layouts.app title="Mis trabajos">
    @if (config('webpush.vapid.public_key') && auth()->user()->pushSubscriptions()->doesntExist())
        <a href="{{ route('avisos.index') }}" class="mb-4 block rounded-md border border-madera-100 bg-white px-4 py-3 text-sm text-madera-700">
            Activá los avisos para enterarte en el celular cuando te asignen un trabajo.
        </a>
    @endif
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($orders as $order)
            <a href="{{ route('taller.trabajos.show', $order) }}" class="card block hover:border-madera-600">
                <div class="flex items-start justify-between gap-2">
                    <span class="text-xs text-stone-500">{{ $order->code }}</span>
                    <span class="badge">{{ $order->stage->name }}</span>
                </div>
                <h2 class="mt-1 font-semibold text-stone-900">{{ $order->title }}</h2>
                <p class="text-sm text-stone-600">{{ $order->client->name }} · {{ $order->install_address ?: $order->client->locationLabel() }}</p>
                <p class="mt-2 text-sm">
                    @if ($order->priority === \App\Enums\Priority::Alta)<span class="badge !bg-red-100 !text-red-800">Prioridad alta</span>@endif
                    @if ($order->estimated_delivery)
                        <span @class(['text-red-700' => $order->estimated_delivery->isPast()])>Entrega {{ $order->estimated_delivery->format('d/m/Y') }}</span>
                    @endif
                    @if ($order->pivot->is_lead)<span class="text-stone-500">· Sos responsable</span>@endif
                </p>
                @if ($order->pending_tasks_count)
                    <p class="mt-2 text-sm text-amber-800">{{ $order->pending_tasks_count }} indicación(es) pendiente(s)</p>
                @endif
            </a>
        @empty
            <p class="text-stone-500">No tenés trabajos asignados por ahora.</p>
        @endforelse
    </div>
</x-layouts.app>
