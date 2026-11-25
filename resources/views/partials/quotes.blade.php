{{-- Presupuestos del trabajo (solo admin). --}}
<section class="card">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <h2 class="font-semibold">Presupuestos</h2>
        <div class="flex gap-2">
            @if ($order->quotes->isNotEmpty())
                <form method="POST" action="{{ route('admin.presupuestos.store', $order) }}">@csrf<input type="hidden" name="copy" value="1"><button class="btn">Nueva versión desde la última</button></form>
            @endif
            <form method="POST" action="{{ route('admin.presupuestos.store', $order) }}">@csrf<button class="btn-primary">Nuevo presupuesto</button></form>
        </div>
    </div>
    @forelse ($order->quotes as $quote)
        <a href="{{ route('admin.presupuestos.edit', $quote) }}" class="flex flex-wrap items-center justify-between gap-2 border-b border-stone-100 py-2 text-sm last:border-0">
            <span>Versión {{ $quote->version }} · <span class="font-medium">{{ money($quote->total) }}</span></span>
            <span class="flex items-center gap-2">
                @if ($quote->client_feedback)<span class="text-xs text-stone-500" title="{{ $quote->client_feedback }}">con comentario</span>@endif
                <span class="badge {{ $quote->status->badgeClasses() }}">{{ $quote->status->label() }}</span>
            </span>
        </a>
        @if ($quote->client_feedback)<p class="mb-2 rounded bg-stone-50 p-2 text-sm"><span class="font-medium">Cliente:</span> {{ $quote->client_feedback }}</p>@endif
    @empty
        <p class="text-sm text-stone-500">Todavía no hay presupuestos.</p>
    @endforelse
</section>
