<x-layouts.app :title="$employee->name">
    <x-slot:actions><a href="{{ route('admin.personal.edit', $employee) }}" class="btn">Editar datos</a></x-slot:actions>

    <p class="mb-6 text-sm text-stone-600">
        {{ collect([$employee->dni ? 'DNI '.$employee->dni : null, $employee->cuil ? 'CUIL '.$employee->cuil : null, $employee->phone, $employee->user ? 'Acceso: '.$employee->user->role->label() : 'Sin acceso al sistema'])->filter()->join(' · ') }}
        @unless ($employee->is_active) · <span class="font-semibold text-red-700">Inactivo</span>@endunless
    </p>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="card">
                <h2 class="mb-3 font-semibold">Documentación</h2>
                @if ($types->isEmpty())
                    <p class="text-sm text-stone-500">Primero armá la lista de <a href="{{ route('admin.documentacion.index') }}" class="text-madera-700 underline">tipos de documento</a> (ART, seguro, DNI...).</p>
                @else
                    <ul class="divide-y divide-stone-100 text-sm">
                        @foreach ($types as $type)
                            @php($doc = $employee->currentDocument($type))
                            <li class="flex flex-wrap items-center gap-2 py-2">
                                <span class="min-w-48 flex-1">{{ $type->displayName() }}</span>
                                @include('admin.partials.doc-status', ['doc' => $doc])
                                @if ($doc)
                                    @if ($doc->status() === \App\Enums\DocumentStatus::Vigente)
                                        <span class="text-stone-500">{{ $doc->expires_on ? 'vence '.$doc->expires_on->format('d/m/Y') : 'sin vencimiento' }}</span>
                                    @endif
                                    <a href="{{ route('admin.documentos.show', $doc) }}" target="_blank" class="text-madera-700 underline">Ver archivo</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            @if ($types->isNotEmpty())
                <section class="card">
                    <h2 class="mb-3 font-semibold">Cargar documento</h2>
                    <p class="mb-3 text-sm text-stone-600">Si ya había uno del mismo tipo, queda en el historial y pasa a valer el de vencimiento más lejano.</p>
                    <form method="POST" action="{{ route('admin.personal.documentos.store', $employee) }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2">
                        @csrf
                        <div>
                            <label class="label" for="document_type_id">Documento</label>
                            <select class="input" id="document_type_id" name="document_type_id" required>
                                @foreach ($types as $type)
                                    <option value="{{ $type->id }}" @selected(old('document_type_id') == $type->id)>{{ $type->displayName() }}{{ $type->requires_expiry ? '' : ' (sin vencimiento)' }}</option>
                                @endforeach
                            </select>
                            @error('document_type_id')<p class="error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="label" for="expires_on">Vence el</label>
                            <input class="input" type="date" id="expires_on" name="expires_on" value="{{ old('expires_on') }}">
                            @error('expires_on')<p class="error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="label" for="file">Archivo (PDF o foto)</label>
                            <input class="input" type="file" id="file" name="file" required accept="application/pdf,image/*" capture="environment">
                            @error('file')<p class="error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="label" for="notes">Nota</label>
                            <input class="input" id="notes" name="notes" value="{{ old('notes') }}" maxlength="255" placeholder="Ej.: póliza 12345, aseguradora">
                        </div>
                        <div class="sm:col-span-2"><button class="btn-primary">Cargar</button></div>
                    </form>
                </section>
            @endif

            @if ($employee->documents->isNotEmpty())
                <section class="card overflow-x-auto !p-0">
                    <h2 class="px-4 pt-4 font-semibold sm:px-6">Historial de archivos</h2>
                    <table class="table mt-2">
                        <thead><tr><th>Documento</th><th>Vence</th><th>Nota</th><th>Cargado</th><th></th></tr></thead>
                        <tbody class="divide-y divide-stone-100">
                            @foreach ($employee->documents as $doc)
                                <tr>
                                    <td><a href="{{ route('admin.documentos.show', $doc) }}" target="_blank" class="text-madera-700 underline">{{ $doc->type->displayName() }}</a></td>
                                    <td>{{ $doc->expires_on?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="text-stone-600">{{ $doc->notes }}</td>
                                    <td class="text-stone-500">{{ $doc->created_at->format('d/m/Y') }}{{ $doc->uploader ? ' · '.$doc->uploader->name : '' }}</td>
                                    <td class="text-right">
                                        <form method="POST" action="{{ route('admin.documentos.destroy', $doc) }}" onsubmit="return confirm('¿Eliminar este archivo?')">
                                            @csrf @method('DELETE')
                                            <button class="text-sm text-stone-400 hover:text-red-700">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>
            @endif
        </div>

        <section class="card h-fit">
            <h2 class="mb-3 font-semibold">Barrios</h2>
            @forelse ($barrios as $barrio)
                @php($missing = $employee->missingFor($barrio))
                <div class="border-b border-stone-100 py-2 text-sm last:border-0">
                    <div class="flex items-center justify-between gap-2">
                        <a href="{{ route('admin.barrios.show', $barrio) }}" class="font-medium hover:underline">{{ $barrio->name }}</a>
                        {!! $missing->isEmpty() ? '<span class="font-semibold text-green-700">Puede entrar</span>' : '<span class="font-semibold text-red-700">No puede entrar</span>' !!}
                    </div>
                    @if ($missing->isNotEmpty())
                        <p class="text-stone-500">Falta o venció: {{ $missing->pluck('name')->join(', ') }}</p>
                    @endif
                </div>
            @empty
                <p class="text-sm text-stone-500">Ningún barrio tiene requisitos cargados todavía.</p>
            @endforelse
        </section>
    </div>
</x-layouts.app>
