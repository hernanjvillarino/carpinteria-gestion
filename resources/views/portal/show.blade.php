@php($pending = $order->designs->first(fn ($d) => $d->awaitsClient()))
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

        {{-- Historial de diseños --}}
        <aside class="card h-fit">
            <h2 class="mb-3 font-semibold">Diseños</h2>
            @forelse ($order->designs as $design)
                <div class="flex items-center justify-between border-b border-stone-100 py-2 text-sm last:border-0">
                    <a href="{{ route('disenos.preview', $design) }}" target="_blank" class="text-madera-700 underline">Versión {{ $design->version }}</a>
                    <span class="badge {{ $design->status->badgeClasses() }}">{{ $design->status->label() }}</span>
                </div>
            @empty
                <p class="text-sm text-stone-500">Cuando tengamos el diseño listo te lo mandamos para que lo apruebes.</p>
            @endforelse
        </aside>
    </div>
</x-layouts.app>
