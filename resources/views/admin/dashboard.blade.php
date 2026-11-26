@php($maxStage = max(1, $stageBars->max('count')))
@php($maxDay = max(1, $activityByDay->max('count')))
<x-layouts.app title="Tablero">
    <x-slot:actions><a href="{{ route('admin.trabajos.create') }}" class="btn-primary">Nuevo trabajo</a></x-slot:actions>

    {{-- Indicadores --}}
    <section class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        <a href="{{ route('admin.trabajos.index') }}" class="card !p-4 hover:border-madera-600">
            <div class="text-3xl font-semibold text-stone-900">{{ $kpis['activos'] }}</div>
            <div class="text-sm text-stone-600">Trabajos en curso</div>
        </a>
        <div @class(['card !p-4', 'border-red-200 bg-red-50' => $kpis['atrasados']])>
            <div @class(['text-3xl font-semibold', 'text-red-700' => $kpis['atrasados'], 'text-stone-900' => ! $kpis['atrasados']])>{{ $kpis['atrasados'] }}</div>
            <div class="text-sm text-stone-600">Atrasados</div>
        </div>
        <div class="card !p-4">
            <div class="text-3xl font-semibold text-stone-900">{{ $kpis['proximos'] }}</div>
            <div class="text-sm text-stone-600">Entregas en 7 días</div>
        </div>
        <div class="card !p-4">
            <div class="text-3xl font-semibold text-stone-900">{{ $kpis['entregados_mes'] }}</div>
            <div class="text-sm text-stone-600">Entregados este mes</div>
        </div>
        <div class="card !p-4">
            <div class="text-3xl font-semibold text-stone-900">{{ $kpis['ocupados'] }}<span class="text-lg text-stone-400">/{{ $kpis['carpinteros'] }}</span></div>
            <div class="text-sm text-stone-600">Carpinteros ocupados</div>
        </div>
        <div class="card !p-4">
            <div class="text-3xl font-semibold text-stone-900">{{ $kpis['carpinteros'] - $kpis['ocupados'] }}</div>
            <div class="text-sm text-stone-600">Carpinteros libres</div>
        </div>
    </section>

    {{-- Línea de tiempo --}}
    <section class="card mb-6">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold">Línea de tiempo de los trabajos</h2>
            <div class="flex flex-wrap gap-3 text-xs text-stone-600">
                <span class="flex items-center gap-1"><span class="inline-block h-2.5 w-4 rounded-sm bg-emerald-600"></span> En tiempo</span>
                <span class="flex items-center gap-1"><span class="inline-block h-2.5 w-4 rounded-sm bg-amber-500"></span> Avance por debajo del plazo</span>
                <span class="flex items-center gap-1"><span class="inline-block h-2.5 w-4 rounded-sm bg-red-600"></span> Fecha de entrega vencida</span>
                <span class="flex items-center gap-1"><span class="inline-block h-3 w-0.5 bg-madera-700"></span> Hoy</span>
            </div>
        </div>
        @if (empty($timeline['rows']) || $timeline['rows']->isEmpty())
            <p class="text-sm text-stone-500">No hay trabajos en curso.</p>
        @else
            <div class="overflow-x-auto">
                <div class="min-w-[640px]">
                    <div class="relative ml-48 h-5 border-b border-stone-200 text-xs text-stone-500">
                        @foreach ($timeline['months'] as $month)
                            <span class="absolute -translate-x-1/2" style="left: {{ $month['left'] }}%">{{ ucfirst($month['label']) }}</span>
                        @endforeach
                    </div>
                    @foreach ($timeline['rows'] as $row)
                        @php($o = $row['order'])
                        @php($color = ['en_tiempo' => 'bg-emerald-600', 'en_riesgo' => 'bg-amber-500', 'atrasado' => 'bg-red-600'][$row['status']])
                        <div class="flex items-center border-b border-stone-100 py-1.5 last:border-0">
                            <a href="{{ route('admin.trabajos.show', $o) }}" class="w-48 shrink-0 truncate pr-3 text-sm hover:underline" title="{{ $o->title }}">
                                <span class="font-medium">{{ $o->code }}</span> <span class="text-stone-500">{{ $o->client->name }}</span>
                            </a>
                            <div class="relative h-6 flex-1">
                                <div class="absolute inset-y-0 w-0.5 bg-madera-700/70" style="left: {{ $timeline['todayPct'] }}%"></div>
                                <div class="absolute inset-y-1 overflow-hidden rounded bg-stone-200" style="left: {{ $row['left'] }}%; width: {{ $row['width'] }}%"
                                     title="{{ $o->code }} · {{ $o->stage->name }} · {{ $row['progress'] }}% · entrega {{ $o->estimated_delivery->format('d/m/Y') }}">
                                    <div class="h-full {{ $color }}" style="width: {{ $row['progress'] }}%"></div>
                                </div>
                                @php($end = min(100, $row['left'] + $row['width']))
                                {{-- La etiqueta va a la derecha de la barra, o a la izquierda si la barra llega al borde. --}}
                                <span class="absolute top-1 text-[11px] whitespace-nowrap text-stone-600"
                                      style="{{ $end > 72 ? 'right: calc('.(100 - $row['left']).'% + 4px)' : 'left: calc('.$end.'% + 4px)' }}">
                                    {{ $row['progress'] }}% · {{ $o->stage->name }} · {{ $o->estimated_delivery->format('d/m') }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    <div class="mb-6 grid gap-6 lg:grid-cols-2">
        {{-- Trabajos por etapa --}}
        <section class="card">
            <h2 class="mb-3 font-semibold">Trabajos en curso por etapa</h2>
            <div class="space-y-1.5">
                @foreach ($stageBars as $bar)
                    <a href="{{ route('admin.trabajos.index', ['stage_id' => $bar['stage']->id]) }}" class="flex items-center gap-3 text-sm hover:underline">
                        <span class="w-40 shrink-0 truncate text-stone-600">{{ $bar['stage']->name }}</span>
                        <span class="relative h-5 flex-1 rounded bg-stone-100">
                            <span class="absolute inset-y-0 left-0 rounded bg-madera-600" style="width: {{ round($bar['count'] / $maxStage * 100, 1) }}%"></span>
                        </span>
                        <span class="w-6 text-right font-medium">{{ $bar['count'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- Carpinteros --}}
        <section class="card">
            <h2 class="mb-3 font-semibold">Carpinteros</h2>
            <div class="divide-y divide-stone-100">
                @forelse ($carpinteros as $c)
                    <div class="flex items-start justify-between gap-3 py-2 text-sm">
                        <div>
                            <a href="{{ route('admin.trabajos.index', ['carpintero_id' => $c->id]) }}" class="font-medium hover:underline">{{ $c->name }}</a>
                            <div class="text-xs text-stone-500">
                                @if ($c->assignedWorkOrders->isNotEmpty())
                                    {{ $c->assignedWorkOrders->pluck('code')->join(', ') }}
                                @endif
                                @if ($c->last_update_at) · último avance {{ \Illuminate\Support\Carbon::parse($c->last_update_at)->diffForHumans() }} @endif
                            </div>
                        </div>
                        @if ($c->active_count)
                            <span class="badge shrink-0 bg-amber-100 text-amber-800">Ocupado · {{ $c->active_count }}</span>
                        @else
                            <span class="badge shrink-0 bg-emerald-100 text-emerald-800">Libre</span>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-stone-500">No hay carpinteros cargados.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Actividad en vivo --}}
        <section class="card">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-semibold">Actividad en vivo</h2>
                <span class="flex items-center gap-1.5 text-xs text-stone-500"><span class="h-2 w-2 animate-pulse rounded-full bg-emerald-500"></span> se actualiza sola</span>
            </div>
            <div class="mb-4 flex h-16 items-end gap-1" aria-label="Avances por día, últimos 14 días">
                @foreach ($activityByDay as $day)
                    <div class="flex flex-1 flex-col items-center gap-1" title="{{ $day['date']->format('d/m') }}: {{ $day['count'] }} avance(s)">
                        <div class="w-full rounded-t bg-madera-600/80" style="height: {{ $day['count'] ? max(4, round($day['count'] / $maxDay * 48)) : 1 }}px"></div>
                        <span class="text-[10px] text-stone-400">{{ $day['date']->format('d') }}</span>
                    </div>
                @endforeach
            </div>
            <div data-live-activity="{{ route('admin.dashboard.activity') }}">
                @include('admin.partials.activity')
            </div>
        </section>

        <section class="card space-y-6">
            <div>
                <div class="mb-2 flex items-baseline justify-between">
                    <h2 class="font-semibold">Satisfacción de clientes (último año)</h2>
                    @if ($satisfaction['overall'])
                        <span class="text-lg font-semibold text-amber-600">{{ number_format($satisfaction['overall'], 1, ',', '.') }} <span class="text-sm text-stone-500">/ 5 · {{ $satisfaction['count'] }} calificación(es)</span></span>
                    @endif
                </div>
                @if ($satisfaction['count'])
                    <div class="space-y-1.5">
                        @foreach ($satisfaction['criteria'] as $c)
                            <div class="flex items-center gap-3 text-sm">
                                <span class="w-32 shrink-0 text-stone-600">{{ $c['label'] }}</span>
                                <span class="relative h-4 flex-1 rounded bg-stone-100">
                                    <span @class(['absolute inset-y-0 left-0 rounded', 'bg-emerald-600' => $c['avg'] >= 4, 'bg-amber-500' => $c['avg'] >= 3 && $c['avg'] < 4, 'bg-red-600' => $c['avg'] < 3]) style="width: {{ $c['avg'] / 5 * 100 }}%"></span>
                                </span>
                                <span class="w-8 text-right font-medium">{{ number_format($c['avg'], 1, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                    @foreach ($satisfaction['latest'] as $rating)
                        <a href="{{ route('admin.trabajos.show', $rating->workOrder) }}#calificacion" class="mt-2 block border-t border-stone-100 pt-2 text-sm">
                            <span class="font-medium">{{ $rating->workOrder->code }}</span> · {{ $rating->workOrder->client->name }}
                            <span class="text-amber-600">{{ number_format($rating->average(), 1, ',', '.') }}★</span>
                            @if ($rating->comment)<span class="block text-stone-600">“{{ str($rating->comment)->limit(110) }}”</span>@endif
                        </a>
                    @endforeach
                @else
                    <p class="text-sm text-stone-500">Todavía no hay calificaciones. Se le piden al cliente cuando el trabajo pasa a Entregado.</p>
                @endif
            </div>
            <div>
                <h2 class="mb-2 font-semibold">Sin carpintero asignado</h2>
                @forelse ($unassigned as $order)
                    <a href="{{ route('admin.trabajos.edit', $order) }}" class="block border-b border-stone-100 py-1.5 text-sm last:border-0">{{ $order->code }} · {{ $order->title }} <span class="text-stone-500">({{ $order->client->name }})</span></a>
                @empty
                    <p class="text-sm text-stone-500">Todos los trabajos en curso tienen carpintero.</p>
                @endforelse
            </div>
            <div>
                <h2 class="mb-2 font-semibold">Respuestas de clientes a diseños</h2>
                @forelse ($designResponses as $design)
                    <a href="{{ route('admin.trabajos.show', $design->workOrder) }}" class="flex justify-between border-b border-stone-100 py-1.5 text-sm last:border-0">
                        <span>{{ $design->workOrder->code }} · {{ $design->workOrder->client->name }} · v{{ $design->version }}</span>
                        <span class="badge {{ $design->status->badgeClasses() }}">{{ $design->status->label() }}</span>
                    </a>
                @empty
                    <p class="text-sm text-stone-500">Sin respuestas en los últimos 14 días.</p>
                @endforelse
            </div>
            <div>
                <h2 @class(['mb-2 font-semibold', 'text-red-800' => $lowStock->isNotEmpty()])>Insumos bajo el mínimo</h2>
                @forelse ($lowStock as $m)
                    <a href="{{ route('admin.stock.show', $m) }}" class="flex justify-between border-b border-stone-100 py-1.5 text-sm last:border-0">
                        <span>{{ $m->name }}</span><span class="text-red-700">{{ qty($m->stock_qty) }} / {{ qty($m->min_stock) }} {{ $m->unit->label() }}</span>
                    </a>
                @empty
                    <p class="text-sm text-stone-500">Todo el stock está por encima del mínimo.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts.app>
