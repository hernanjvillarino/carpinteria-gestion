@php($rating = $order->rating)
<section id="calificacion" class="card border-emerald-200">
    <div class="mb-3 flex items-center justify-between">
        <h2 class="font-semibold">Calificación del cliente</h2>
        <span class="text-lg font-semibold text-amber-600">{{ number_format($rating->average(), 1, ',', '.') }} <span class="text-sm text-stone-500">/ 5</span></span>
    </div>
    <dl class="grid gap-x-6 gap-y-1 text-sm sm:grid-cols-2">
        @foreach (\App\Models\WorkOrderRating::CRITERIA as $field => $label)
            <div class="flex justify-between border-b border-stone-100 py-1">
                <dt class="text-stone-600">{{ $label }}</dt>
                <dd class="text-amber-500" aria-label="{{ $rating->{$field} }} de 5">{{ str_repeat('★', $rating->{$field}) }}<span class="text-stone-300">{{ str_repeat('★', 5 - $rating->{$field}) }}</span></dd>
            </div>
        @endforeach
    </dl>
    @if ($rating->comment)<p class="mt-3 text-sm whitespace-pre-line">“{{ $rating->comment }}”</p>@endif
    @if ($rating->photos->isNotEmpty())
        <div class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-6">
            @foreach ($rating->photos as $photo)
                <a href="{{ route('calificaciones.fotos', $photo) }}" target="_blank"><img src="{{ route('calificaciones.fotos', $photo) }}" alt="Foto del cliente" class="aspect-square w-full rounded object-cover" loading="lazy"></a>
            @endforeach
        </div>
    @endif
    <p class="mt-2 text-xs text-stone-500">{{ $rating->user->name ?? 'Cliente' }} · {{ $rating->created_at->format('d/m/Y') }}</p>
</section>
