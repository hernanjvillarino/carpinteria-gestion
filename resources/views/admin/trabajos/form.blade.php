@php($assigned = old('carpinteros', $order->exists ? $order->carpenters->pluck('id')->all() : []))
@php($leadId = old('lead_id', $order->exists ? $order->carpenters->firstWhere('pivot.is_lead', true)?->id : null))
<x-layouts.app :title="$order->exists ? 'Editar '.$order->code : 'Nuevo trabajo'">
    <form method="POST" action="{{ $order->exists ? route('admin.trabajos.update', $order) : route('admin.trabajos.store') }}" class="card max-w-3xl space-y-4">
        @csrf
        @if ($order->exists) @method('PUT') @endif
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="client_id">Cliente</label>
                <select class="input" id="client_id" name="client_id" required>
                    <option value="">Elegí un cliente</option>
                    @foreach ($clientes as $cliente)
                        <option value="{{ $cliente->id }}" @selected(old('client_id', $order->client_id) == $cliente->id)>{{ $cliente->name }}</option>
                    @endforeach
                </select>
                @error('client_id')<p class="error">{{ $message }}</p>@enderror
                <a href="{{ route('admin.clientes.create') }}" class="mt-1 inline-block text-xs text-madera-700 underline">Crear cliente nuevo</a>
            </div>
            <div>
                <label class="label" for="title">Título</label>
                <input class="input" id="title" name="title" value="{{ old('title', $order->title) }}" placeholder="Ej.: Cocina completa con isla" required>
                @error('title')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label class="label" for="description">Descripción</label>
                <textarea class="input" id="description" name="description" rows="4">{{ old('description', $order->description) }}</textarea>
            </div>
            <div class="sm:col-span-2">
                <label class="label" for="install_address">Dirección de instalación (si es distinta a la del cliente)</label>
                <input class="input" id="install_address" name="install_address" value="{{ old('install_address', $order->install_address) }}">
            </div>
            <div>
                <label class="label" for="stage_id">Etapa</label>
                <select class="input" id="stage_id" name="stage_id">
                    @foreach ($stages as $stage)
                        <option value="{{ $stage->id }}" @selected(old('stage_id', $order->stage_id ?? $stages->first()->id) == $stage->id)>{{ $stage->position }}. {{ $stage->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="priority">Prioridad</label>
                <select class="input" id="priority" name="priority">
                    @foreach ($priorities as $p)
                        <option value="{{ $p->value }}" @selected(old('priority', $order->priority?->value) === $p->value)>{{ $p->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:col-span-2">
                <div>
                    <label class="label" for="started_on">Inicio del plazo</label>
                    <input class="input" id="started_on" type="date" name="started_on" value="{{ old('started_on', $order->started_on?->toDateString()) }}" required>
                    @error('started_on')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="lead_time_days">Plazo (días)</label>
                    <input class="input" id="lead_time_days" type="number" min="1" max="730" name="lead_time_days" value="{{ old('lead_time_days', $order->lead_time_days) }}" required>
                    @error('lead_time_days')<p class="error">{{ $message }}</p>@enderror
                </div>
                <p class="col-span-2 text-xs text-stone-500" data-entrega>
                    La entrega estimada se calcula sola: inicio + plazo.
                    @if ($order->estimated_delivery) Hoy da <strong>{{ $order->estimated_delivery->format('d/m/Y') }}</strong>. @endif
                    Por defecto son {{ config('carpinteria.default_lead_time_days') }} días; para un trabajo grande podés acordar más con el cliente.
                </p>
            </div>
        </div>

        <fieldset>
            <legend class="label">Carpinteros asignados</legend>
            @error('carpinteros.*')<p class="error">{{ $message }}</p>@enderror
            @error('lead_id')<p class="error">El responsable tiene que estar entre los asignados.</p>@enderror
            <div class="divide-y divide-stone-100 rounded-md border border-stone-200">
                @forelse ($carpinteros as $c)
                    <div class="flex items-center justify-between px-3 py-2 text-sm">
                        <label class="flex items-center gap-2"><input type="checkbox" name="carpinteros[]" value="{{ $c->id }}" @checked(in_array($c->id, $assigned))> {{ $c->name }}</label>
                        <label class="flex items-center gap-2 text-stone-500"><input type="radio" name="lead_id" value="{{ $c->id }}" @checked($leadId == $c->id)> Responsable</label>
                    </div>
                @empty
                    <p class="px-3 py-2 text-sm text-stone-500">No hay carpinteros activos. <a class="underline" href="{{ route('admin.usuarios.create') }}">Crear uno</a>.</p>
                @endforelse
            </div>
        </fieldset>

        <fieldset>
            <legend class="label">Indicaciones para los carpinteros</legend>
            @if ($order->exists && $order->tasks->isNotEmpty())
                <ul class="mb-2 space-y-1 text-sm">
                    @foreach ($order->tasks as $task)
                        <li @class(['text-stone-400 line-through' => $task->isDone()])>• {{ $task->text }}</li>
                    @endforeach
                </ul>
                <p class="mb-2 text-xs text-stone-500">Ya cargadas. Lo que tildes abajo se suma.</p>
            @endif
            @php($usedTemplates = $order->exists ? $order->tasks->pluck('instruction_template_id')->filter()->all() : [])
            <div class="grid gap-1 rounded-md border border-stone-200 p-3 text-sm sm:grid-cols-2">
                @forelse ($instructionTemplates->whereNotIn('id', $usedTemplates) as $template)
                    <label class="flex items-start gap-2"><input class="mt-1" type="checkbox" name="instrucciones[]" value="{{ $template->id }}" @checked(in_array($template->id, old('instrucciones', [])))> {{ $template->text }}</label>
                @empty
                    <p class="text-stone-500">No hay indicaciones precargadas para tildar. <a class="underline" href="{{ route('admin.indicaciones.index') }}">Cargá tu lista</a>.</p>
                @endforelse
            </div>
            <label class="label mt-3" for="instrucciones_extra">Otras indicaciones (una por línea)</label>
            <textarea class="input" id="instrucciones_extra" name="instrucciones_extra" rows="2" placeholder="Ej.: Coordinar el acceso al barrio con la guardia">{{ old('instrucciones_extra') }}</textarea>
        </fieldset>

        <div class="flex gap-3"><button class="btn-primary">Guardar</button><a href="{{ $order->exists ? route('admin.trabajos.show', $order) : route('admin.trabajos.index') }}" class="btn">Cancelar</a></div>
    </form>
</x-layouts.app>
