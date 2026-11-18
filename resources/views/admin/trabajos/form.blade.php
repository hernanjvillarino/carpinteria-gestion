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
            <div>
                <label class="label" for="estimated_delivery">Entrega estimada</label>
                <input class="input" id="estimated_delivery" type="date" name="estimated_delivery" value="{{ old('estimated_delivery', $order->estimated_delivery?->toDateString()) }}">
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

        <div class="flex gap-3"><button class="btn-primary">Guardar</button><a href="{{ $order->exists ? route('admin.trabajos.show', $order) : route('admin.trabajos.index') }}" class="btn">Cancelar</a></div>
    </form>
</x-layouts.app>
