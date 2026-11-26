<section id="calificar" class="card mb-6 border-emerald-300">
    @if ($order->rating)
        <h2 class="mb-1 font-semibold">Tu calificación</h2>
        <p class="mb-4 text-sm text-stone-600">¡Gracias por contarnos cómo te fue! La enviaste el {{ $order->rating->created_at->format('d/m/Y') }}.</p>
        <dl class="grid gap-2 text-sm sm:grid-cols-2">
            @foreach (\App\Models\WorkOrderRating::CRITERIA as $field => $label)
                <div class="flex justify-between gap-3 border-b border-stone-100 py-1">
                    <dt class="text-stone-600">{{ $label }}</dt>
                    <dd class="text-amber-500" aria-label="{{ $order->rating->{$field} }} de 5">{{ str_repeat('★', $order->rating->{$field}) }}<span class="text-stone-300">{{ str_repeat('★', 5 - $order->rating->{$field}) }}</span></dd>
                </div>
            @endforeach
        </dl>
        @if ($order->rating->comment)<p class="mt-4 text-sm whitespace-pre-line">“{{ $order->rating->comment }}”</p>@endif
        @if ($order->rating->photos->isNotEmpty())
            <div class="mt-4 grid grid-cols-3 gap-2 sm:grid-cols-6">
                @foreach ($order->rating->photos as $photo)
                    <a href="{{ route('calificaciones.fotos', $photo) }}" target="_blank"><img src="{{ route('calificaciones.fotos', $photo) }}" alt="Foto de tu calificación" class="aspect-square w-full rounded object-cover" loading="lazy"></a>
                @endforeach
            </div>
        @endif
    @else
        <h2 class="mb-1 font-semibold">¿Cómo te fue con tu {{ mb_strtolower($order->title) }}?</h2>
        <p class="mb-4 text-sm text-stone-600">Puntuá cada punto de 1 a 5 estrellas. Tu opinión nos ayuda a mejorar en cada trabajo.</p>
        @error('rating')<p class="error mb-3">{{ $message }}</p>@enderror
        <form method="POST" action="{{ route('portal.trabajos.calificacion', $order) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @foreach (\App\Models\WorkOrderRating::CRITERIA as $field => $label)
                <fieldset>
                    <legend class="mb-1 text-sm font-medium text-stone-700">{{ $label }}</legend>
                    <div class="flex gap-1">
                        @for ($i = 1; $i <= 5; $i++)
                            <label class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-md border border-stone-300 text-lg text-stone-400 has-checked:border-amber-500 has-checked:bg-amber-50 has-checked:text-amber-500 has-focus-visible:ring-2 has-focus-visible:ring-amber-300">
                                <input class="sr-only" type="radio" name="{{ $field }}" value="{{ $i }}" @checked(old($field) == $i) required>
                                <span aria-hidden="true">★</span><span class="sr-only">{{ $i }} de 5</span>
                                <span class="ml-0.5 text-xs">{{ $i }}</span>
                            </label>
                        @endfor
                    </div>
                    @error($field)<p class="error">{{ $message }}</p>@enderror
                </fieldset>
            @endforeach
            <div>
                <label class="label" for="rating-comment">Comentario (opcional)</label>
                <textarea class="input" id="rating-comment" name="comment" rows="3" placeholder="Lo que más te gustó, lo que podríamos mejorar...">{{ old('comment') }}</textarea>
            </div>
            <div>
                <label class="label" for="rating-photos">Fotos de cómo quedó (opcional, hasta 6)</label>
                <input class="input" id="rating-photos" type="file" name="photos[]" accept="image/*" multiple>
                @error('photos')<p class="error">{{ $message }}</p>@enderror
                @error('photos.*')<p class="error">{{ $message }}</p>@enderror
            </div>
            <button class="btn-primary">Enviar calificación</button>
        </form>
    @endif
</section>
