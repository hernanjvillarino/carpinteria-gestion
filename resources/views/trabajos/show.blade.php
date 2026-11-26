{{-- Vista del trabajo compartida por admin y carpintero. --}}
@php($next = $order->stage->next())
<x-layouts.app :title="$order->code.' · '.$order->title">
    @if ($isAdmin)
        <x-slot:actions><a href="{{ route('admin.trabajos.edit', $order) }}" class="btn">Editar trabajo</a></x-slot:actions>
    @endif

    {{-- Barra de etapas --}}
    <ol class="mb-6 flex flex-wrap gap-1 text-xs">
        @foreach ($stages as $stage)
            <li @class([
                'rounded-full px-3 py-1',
                'bg-madera-600 text-white' => $stage->id === $order->stage_id,
                'bg-madera-100 text-madera-900' => $stage->position < $order->stage->position,
                'bg-stone-100 text-stone-500' => $stage->position > $order->stage->position,
            ])>{{ $stage->name }}</li>
        @endforeach
    </ol>

    @if (! empty($accessWarnings))
        <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            <p class="font-semibold">Ojo: no todos los asignados pueden entrar a {{ $order->client->gatedCommunity->name }}.</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach ($accessWarnings as $name => $problem)<li>{{ $name }}: {{ $problem }}</li>@endforeach
            </ul>
            <a href="{{ route('admin.barrios.show', $order->client->gatedCommunity) }}" class="mt-1 inline-block underline">Ver la documentación del barrio</a>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @if ($isAdmin && $order->rating)
                @include('partials.rating')
            @endif

            @include('partials.tasks')

            {{-- Registrar avance --}}
            <section class="card">
                <h2 class="mb-3 font-semibold">Registrar avance</h2>
                <form method="POST" action="{{ route('trabajos.avances.store', $order) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="label" for="stage_id">Etapa</label>
                        <select class="input" id="stage_id" name="stage_id">
                            <option value="">Sigue en «{{ $order->stage->name }}»</option>
                            @if ($isAdmin)
                                @foreach ($stages as $stage)
                                    @continue($stage->id === $order->stage_id)
                                    <option value="{{ $stage->id }}" @selected(old('stage_id') == $stage->id)>Pasar a «{{ $stage->name }}»</option>
                                @endforeach
                            @elseif ($next)
                                <option value="{{ $next->id }}" @selected(old('stage_id') == $next->id)>Pasar a «{{ $next->name }}»</option>
                            @endif
                        </select>
                        @error('stage_id')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="comment">Comentario</label>
                        <textarea class="input" id="comment" name="comment" rows="3" placeholder="Qué se hizo, qué falta, materiales usados">{{ old('comment') }}</textarea>
                        @error('comment')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="photos">Fotos</label>
                        <input class="input" id="photos" type="file" name="photos[]" accept="image/*" multiple capture="environment">
                        @error('photos.*')<p class="error">{{ $message }}</p>@enderror
                        @error('photos')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    @if ($isAdmin)
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="visible_to_client" value="1"> Mostrar este avance y sus fotos al cliente</label>
                    @else
                        <p class="text-xs text-stone-500">La administración decide qué fotos ve el cliente.</p>
                    @endif
                    <button class="btn-primary">Guardar avance</button>
                </form>
            </section>

            @if ($isAdmin)
                @include('partials.quotes')
            @endif

            @include('partials.designs')

            @include('partials.consumption')

            {{-- Historial --}}
            <section class="card">
                <h2 class="mb-3 font-semibold">Historial</h2>
                <ol class="space-y-4">
                    @forelse ($order->updates as $update)
                        <li class="border-l-2 border-madera-100 pl-4">
                            <div class="text-sm">
                                <span class="font-medium">{{ $update->user->name }}</span>
                                <span class="text-stone-500">· {{ $update->created_at->format('d/m/Y H:i') }}</span>
                                @if ($update->toStage)
                                    · {{ $update->fromStage?->name }} → <span class="badge">{{ $update->toStage->name }}</span>
                                @endif
                                @if ($update->visible_to_client)<span class="badge !bg-green-100 !text-green-800">Visible al cliente</span>@endif
                            </div>
                            @if ($update->comment)<p class="mt-1 text-sm whitespace-pre-line">{{ $update->comment }}</p>@endif
                            @if ($update->photos->isNotEmpty())
                                <div class="mt-2 grid grid-cols-3 gap-2 sm:grid-cols-4">
                                    @foreach ($update->photos as $photo)
                                        <figure>
                                            <a href="{{ route('fotos.show', $photo) }}" target="_blank"><img src="{{ route('fotos.show', $photo) }}" alt="Foto del avance" loading="lazy" class="aspect-square w-full rounded object-cover"></a>
                                            @if ($isAdmin)
                                                <form method="POST" action="{{ route('admin.fotos.visibilidad', $photo) }}" class="mt-1">
                                                    @csrf @method('PATCH')
                                                    <button class="text-xs underline {{ $photo->visible_to_client ? 'text-green-700' : 'text-stone-500' }}">{{ $photo->visible_to_client ? 'La ve el cliente' : 'Oculta al cliente' }}</button>
                                                </form>
                                            @endif
                                        </figure>
                                    @endforeach
                                </div>
                            @endif
                        </li>
                    @empty
                        <li class="text-sm text-stone-500">Todavía no hay avances registrados.</li>
                    @endforelse
                </ol>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="card space-y-2 text-sm">
                <h2 class="font-semibold">Datos del trabajo</h2>
                <p><span class="text-stone-500">Cliente:</span>
                    @if ($isAdmin)<a class="text-madera-700 underline" href="{{ route('admin.clientes.show', $order->client) }}">{{ $order->client->name }}</a>@else{{ $order->client->name }}@endif
                </p>
                <p><span class="text-stone-500">Teléfono:</span> {{ $order->client->phone ?: '—' }}</p>
                <p><span class="text-stone-500">Lugar:</span> {{ $order->install_address ?: $order->client->locationLabel() }}</p>
                <p><span class="text-stone-500">Prioridad:</span> {{ $order->priority->label() }}</p>
                <p><span class="text-stone-500">Plazo:</span> {{ $order->lead_time_days }} días desde el {{ $order->started_on?->format('d/m/Y') ?? '—' }}</p>
                <p><span class="text-stone-500">Entrega estimada:</span>
                    <span @class(['font-medium text-red-700' => ! $order->delivered_at && $order->estimated_delivery?->isPast()])>{{ $order->estimated_delivery?->format('d/m/Y') ?? '—' }}</span>
                    @if (! $order->delivered_at && $order->estimated_delivery)
                        <span class="text-stone-500">({{ $order->estimated_delivery->isPast() ? 'venció '.$order->estimated_delivery->diffForHumans() : 'faltan '.(int) today()->diffInDays($order->estimated_delivery).' días' }})</span>
                    @endif
                </p>
                @if ($order->delivered_at)<p><span class="text-stone-500">Entregado:</span> {{ $order->delivered_at->format('d/m/Y') }}</p>@endif
                <p><span class="text-stone-500">Carpinteros:</span>
                    {{ $order->carpenters->map(fn ($c) => $c->name.($c->pivot->is_lead ? ' (responsable)' : ''))->join(', ') ?: 'Sin asignar' }}
                </p>
                @if ($order->client->gatedCommunity?->access_notes)
                    <p class="rounded bg-amber-50 p-2 text-amber-900"><span class="font-medium">Ingreso al barrio:</span> {{ $order->client->gatedCommunity->access_notes }}</p>
                @endif
            </section>
            @if ($order->description)
                <section class="card text-sm"><h2 class="mb-2 font-semibold">Descripción</h2><p class="whitespace-pre-line">{{ $order->description }}</p></section>
            @endif
        </aside>
    </div>
</x-layouts.app>
