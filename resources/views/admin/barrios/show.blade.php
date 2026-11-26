<x-layouts.app :title="$barrio->name">
    <x-slot:actions><a href="{{ route('admin.barrios.edit', $barrio) }}" class="btn">Editar barrio y requisitos</a></x-slot:actions>

    @php($enabled = $employees->filter(fn ($e) => $e->isEnabledFor($barrio)))
    <div class="grid gap-6 lg:grid-cols-3">
        <section class="card lg:col-span-2 overflow-x-auto">
            <h2 class="mb-1 font-semibold">Personal habilitado para entrar</h2>
            @if ($types->isEmpty())
                <p class="text-sm text-stone-500">Todavía no cargaste qué documentación pide este barrio. <a href="{{ route('admin.barrios.edit', $barrio) }}" class="text-madera-700 underline">Cargala</a> para ver quién puede entrar.</p>
            @else
                <p class="mb-4 text-sm text-stone-600">{{ $enabled->count() }} de {{ $employees->count() }} con todo vigente. Los documentos «por vencer» todavía sirven, pero conviene renovarlos.</p>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            @foreach ($types as $type)<th>{{ $type->name }}</th>@endforeach
                            <th>¿Puede entrar?</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @forelse ($employees as $employee)
                            <tr>
                                <td class="font-medium"><a href="{{ route('admin.personal.show', $employee) }}" class="hover:underline">{{ $employee->name }}</a></td>
                                @foreach ($types as $type)
                                    <td>@include('admin.partials.doc-status', ['doc' => $employee->currentDocument($type)])</td>
                                @endforeach
                                <td>{!! $enabled->contains($employee) ? '<span class="font-semibold text-green-700">Sí</span>' : '<span class="font-semibold text-red-700">No</span>' !!}</td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $types->count() + 2 }}" class="text-stone-500">No hay personal cargado. <a href="{{ route('admin.personal.create') }}" class="text-madera-700 underline">Sumá el primero</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </section>

        <div class="space-y-6">
            <section class="card">
                <h2 class="mb-2 font-semibold">Qué pide</h2>
                @if ($types->isEmpty())
                    <p class="text-sm text-stone-500">Sin requisitos cargados.</p>
                @else
                    <ul class="list-inside list-disc text-sm">
                        @foreach ($types as $type)
                            <li>{{ $type->name }}@if ($type->gated_community_id) <span class="text-stone-500">(propio del barrio)</span>@endif</li>
                        @endforeach
                    </ul>
                @endif
                @if ($barrio->access_notes)
                    <p class="mt-3 whitespace-pre-line text-sm text-stone-600">{{ $barrio->access_notes }}</p>
                @endif
            </section>

            @if ($types->isNotEmpty() && $enabled->isNotEmpty())
                <section class="card">
                    <h2 class="mb-2 font-semibold">Nómina para la administración</h2>
                    <p class="mb-2 text-xs text-stone-500">Personal habilitado, listo para copiar y mandar al barrio.</p>
                    <textarea class="input font-mono text-xs" rows="{{ min(10, $enabled->count() + 1) }}" readonly onclick="this.select()">{{ $enabled->map(fn ($e) => collect([$e->name, $e->dni ? 'DNI '.$e->dni : null, $e->cuil ? 'CUIL '.$e->cuil : null])->filter()->join(' · '))->join("\n") }}</textarea>
                </section>
            @endif

            <section class="card">
                <h2 class="mb-2 font-semibold">Clientes en el barrio</h2>
                <ul class="text-sm">
                    @forelse ($barrio->clients as $client)
                        <li><a href="{{ route('admin.clientes.show', $client) }}" class="text-madera-700 underline">{{ $client->name }}</a>@if ($client->lot) <span class="text-stone-500">· lote {{ $client->lot }}</span>@endif</li>
                    @empty
                        <li class="text-stone-500">Ninguno todavía.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>
</x-layouts.app>
