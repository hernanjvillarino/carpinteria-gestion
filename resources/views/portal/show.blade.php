@php($pending = $order->designs->first(fn ($d) => $d->awaitsClient()))
@php($pendingQuote = $order->quotes->first(fn ($q) => $q->status === \App\Enums\QuoteStatus::Enviado && ! $q->isExpired()))
<x-layouts.app :title="$order->title">
    <p class="-mt-4 mb-6 text-sm text-stone-500">Pedido {{ $order->code }}
        @if ($order->delivered_at) · Entregado el {{ $order->delivered_at->format('d/m/Y') }}
        @elseif ($order->estimated_delivery) · Entrega estimada {{ $order->estimated_delivery->format('d/m/Y') }}
        @endif
    </p>

    {{-- Etapas --}}
    <section class="card mb-6">
        <h2 class="mb-4 font-semibold">En qué etapa está</h2>
        <ol class="grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ($stages as $stage)
                @php($state = $stage->position < $order->stage->position ? 'done' : ($stage->id === $order->stage_id ? 'current' : 'next'))
                <li @class([
                    'flex items-center gap-2 rounded-md border px-3 py-2 text-sm',
                    'border-madera-100 bg-madera-50 text-madera-900' => $state === 'done',
                    'border-madera-600 bg-madera-600 font-semibold text-white' => $state === 'current',
                    'border-stone-200 text-stone-400' => $state === 'next',
                ])>
                    <span aria-hidden="true">{{ $state === 'done' ? '✓' : ($state === 'current' ? '●' : '○') }}</span>
                    {{ $stage->name }}
                    @if ($state === 'current')<span class="sr-only">(etapa actual)</span>@endif
                </li>
            @endforeach
        </ol>
        @unless ($order->stage->visible_to_client)
            <p class="mt-3 text-sm text-stone-600">Ahora estamos preparando la producción de tu pedido.</p>
        @endunless
    </section>

    {{-- Presupuesto para responder --}}
    @if ($pendingQuote)
        <section class="card mb-6 border-amber-300">
            <h2 class="mb-1 font-semibold">Presupuesto para revisar</h2>
            <p class="mb-4 text-sm text-stone-600">Versión {{ $pendingQuote->version }}@if ($pendingQuote->valid_until), válido hasta el {{ $pendingQuote->valid_until->format('d/m/Y') }}@endif.</p>
            <ul class="mb-4 divide-y divide-stone-100 text-sm">
                @foreach ($pendingQuote->items as $item)
                    <li class="flex justify-between py-1"><span>{{ $item->description }}</span><span class="text-stone-500">{{ qty($item->qty) }} {{ $item->unit }}</span></li>
                @endforeach
                @if ((float) $pendingQuote->labor_cost > 0)<li class="py-1">Mano de obra, fabricación e instalación</li>@endif
            </ul>
            <dl class="mb-4 space-y-1 text-sm">
                <div class="flex justify-between"><dt class="text-stone-500">Subtotal</dt><dd>{{ money($pendingQuote->subtotal) }}</dd></div>
                <div class="flex justify-between"><dt class="text-stone-500">IVA {{ qty($pendingQuote->tax_pct) }}%</dt><dd>{{ money($pendingQuote->taxAmount()) }}</dd></div>
                <div class="flex justify-between text-lg font-semibold"><dt>Total</dt><dd>{{ money($pendingQuote->total) }}</dd></div>
            </dl>
            @if ($pendingQuote->notes)<p class="mb-4 text-sm whitespace-pre-line">{{ $pendingQuote->notes }}</p>@endif
            @error('quote')<p class="error mb-2">{{ $message }}</p>@enderror
            <form method="POST" action="{{ route('portal.presupuestos.answer', $pendingQuote) }}" class="space-y-3">
                @csrf
                <label for="quote_feedback" class="label">Comentario (opcional)</label>
                <textarea id="quote_feedback" name="feedback" rows="2" class="input" placeholder="Dudas o ajustes que quieras pedir">{{ old('feedback') }}</textarea>
                <div class="grid gap-2 sm:grid-cols-3">
                    <button name="decision" value="aceptar" class="btn-primary" onclick="return confirm('¿Confirmás que aceptás el presupuesto?')">Acepto el presupuesto</button>
                    <button name="decision" value="rechazar" class="btn">No lo acepto</button>
                    <a href="{{ route('portal.presupuestos.pdf', $pendingQuote) }}" class="btn">Descargar PDF</a>
                </div>
            </form>
        </section>
    @endif

    {{-- Diseño para aprobar --}}
    @if ($pending)
        <section class="card mb-6 border-amber-300">
            <h2 class="mb-1 font-semibold">Diseño para aprobar · versión {{ $pending->version }}</h2>
            <p class="mb-4 text-sm text-stone-600">Revisá el diseño. Si está todo bien aprobalo; si querés cambiar algo, contanos qué.</p>
            <a href="{{ route('disenos.preview', $pending) }}" target="_blank"><img src="{{ route('disenos.preview', $pending) }}" alt="Diseño versión {{ $pending->version }}" class="mb-4 max-h-[32rem] w-full rounded border border-stone-200 object-contain"></a>
            @if ($pending->notes)<p class="mb-4 text-sm whitespace-pre-line">{{ $pending->notes }}</p>@endif
            @error('design')<p class="error mb-2">{{ $message }}</p>@enderror
            <div class="grid gap-4 sm:grid-cols-2">
                <form method="POST" action="{{ route('portal.disenos.answer', $pending) }}" onsubmit="return confirm('¿Confirmás que aprobás este diseño? Con la aprobación empezamos a fabricar.')">
                    @csrf
                    <input type="hidden" name="decision" value="aprobar">
                    <button class="btn-primary w-full">Apruebo el diseño</button>
                </form>
                <form method="POST" action="{{ route('portal.disenos.answer', $pending) }}" class="space-y-2">
                    @csrf
                    <input type="hidden" name="decision" value="cambios">
                    <label for="feedback" class="label">¿Qué querés cambiar?</label>
                    <textarea id="feedback" name="feedback" rows="3" class="input" placeholder="Por ejemplo: la alacena 10 cm más alta y manijas negras">{{ old('feedback') }}</textarea>
                    @error('feedback')<p class="error">{{ $message }}</p>@enderror
                    <button class="btn w-full">Pedir cambios</button>
                </form>
            </div>
        </section>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Avances --}}
        <section class="card lg:col-span-2">
            <h2 class="mb-4 font-semibold">Avances</h2>
            <ol class="space-y-5">
                @forelse ($order->updates as $update)
                    <li class="border-l-2 border-madera-100 pl-4">
                        <p class="text-sm text-stone-500">{{ $update->created_at->format('d/m/Y') }}</p>
                        @if ($update->toStage?->visible_to_client)
                            <p class="font-medium">Pasó a la etapa «{{ $update->toStage->name }}»</p>
                        @endif
                        @if ($update->visible_to_client && $update->comment)
                            <p class="mt-1 text-sm whitespace-pre-line">{{ $update->comment }}</p>
                        @endif
                        @if ($update->photos->isNotEmpty())
                            <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                                @foreach ($update->photos as $photo)
                                    <a href="{{ route('fotos.show', $photo) }}" target="_blank"><img src="{{ route('fotos.show', $photo) }}" alt="Foto del avance del {{ $update->created_at->format('d/m/Y') }}" loading="lazy" class="aspect-square w-full rounded object-cover"></a>
                                @endforeach
                            </div>
                        @endif
                    </li>
                @empty
                    <li class="text-sm text-stone-500">Todavía no hay avances para mostrar. Te avisamos por email cuando tu pedido cambie de etapa.</li>
                @endforelse
            </ol>
        </section>

        {{-- Historial de diseños y presupuestos --}}
        <aside class="card h-fit space-y-6">
            @if ($order->quotes->isNotEmpty())
                <div>
                    <h2 class="mb-3 font-semibold">Presupuestos</h2>
                    @foreach ($order->quotes as $q)
                        <div class="flex items-center justify-between border-b border-stone-100 py-2 text-sm last:border-0">
                            <a href="{{ route('portal.presupuestos.pdf', $q) }}" class="text-madera-700 underline">Versión {{ $q->version }} · {{ money($q->total) }}</a>
                            <span class="badge {{ ($q->isExpired() ? \App\Enums\QuoteStatus::Vencido : $q->status)->badgeClasses() }}">{{ $q->isExpired() ? 'Vencido' : $q->status->label() }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
            <div>
            <h2 class="mb-3 font-semibold">Diseños</h2>
            @forelse ($order->designs as $design)
                <div class="flex items-center justify-between border-b border-stone-100 py-2 text-sm last:border-0">
                    <a href="{{ route('disenos.preview', $design) }}" target="_blank" class="text-madera-700 underline">Versión {{ $design->version }}</a>
                    <span class="badge {{ $design->status->badgeClasses() }}">{{ $design->status->label() }}</span>
                </div>
            @empty
                <p class="text-sm text-stone-500">Cuando tengamos el diseño listo te lo mandamos para que lo apruebes.</p>
            @endforelse
            </div>
        </aside>
    </div>
</x-layouts.app>
