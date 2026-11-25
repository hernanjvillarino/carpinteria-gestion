{{-- Diseños del trabajo, en la vista de admin y carpintero. --}}
<section class="card">
    <h2 class="mb-3 font-semibold">Diseños SketchUp</h2>
    @forelse ($order->designs as $design)
        <div class="flex flex-wrap items-start gap-3 border-b border-stone-100 py-3 last:border-0">
            <a href="{{ route('disenos.preview', $design) }}" target="_blank" class="shrink-0"><img src="{{ route('disenos.preview', $design) }}" alt="Diseño versión {{ $design->version }}" class="h-20 w-28 rounded border border-stone-200 object-cover"></a>
            <div class="min-w-0 flex-1 text-sm">
                <p class="font-medium">Versión {{ $design->version }} <span class="badge {{ $design->status->badgeClasses() }}">{{ $design->status->label() }}</span></p>
                @if ($design->notes)<p class="text-stone-600">{{ $design->notes }}</p>@endif
                @if ($design->client_feedback)<p class="mt-1 rounded bg-red-50 p-2 text-red-900"><span class="font-medium">Pedido del cliente:</span> {{ $design->client_feedback }}</p>@endif
                <p class="text-xs text-stone-500">
                    @if ($design->approved_at) Aprobado el {{ $design->approved_at->format('d/m/Y H:i') }}
                    @elseif ($design->responded_at) Respondido el {{ $design->responded_at->format('d/m/Y H:i') }}
                    @elseif ($design->sent_at) Enviado el {{ $design->sent_at->format('d/m/Y H:i') }}
                    @endif
                </p>
                <div class="mt-1 flex flex-wrap gap-3">
                    @if ($design->skp_path)<a href="{{ route('disenos.skp', $design) }}" class="text-madera-700 underline">Descargar .skp</a>@endif
                    @if ($isAdmin && $design->status === \App\Enums\DesignStatus::Borrador)
                        <form method="POST" action="{{ route('admin.disenos.send', $design) }}">@csrf<button class="text-madera-700 underline">Enviar al cliente</button></form>
                        <form method="POST" action="{{ route('admin.disenos.destroy', $design) }}" onsubmit="return confirm('¿Borrar este borrador?')">@csrf @method('DELETE')<button class="text-red-700 underline">Borrar</button></form>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <p class="text-sm text-stone-500">Todavía no hay diseños cargados.</p>
    @endforelse

    @if ($isAdmin)
        <details class="mt-4" @if ($errors->hasAny(['preview', 'skp', 'notes'])) open @endif>
            <summary class="cursor-pointer text-sm font-medium text-madera-700">Subir nueva versión</summary>
            <form method="POST" action="{{ route('admin.disenos.store', $order) }}" enctype="multipart/form-data" class="mt-3 space-y-3">
                @csrf
                <div>
                    <label class="label" for="preview">Imagen del diseño (exportada de SketchUp)</label>
                    <input class="input" id="preview" type="file" name="preview" accept="image/*" required>
                    @error('preview')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="skp">Archivo SketchUp (.skp, opcional)</label>
                    <input class="input" id="skp" type="file" name="skp" accept=".skp">
                    @error('skp')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="design_notes">Notas para el cliente</label>
                    <textarea class="input" id="design_notes" name="notes" rows="2" placeholder="Materiales, colores, medidas generales">{{ old('notes') }}</textarea>
                </div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="send" value="1" checked> Enviar al cliente ahora</label>
                <button class="btn-primary">Guardar versión</button>
            </form>
        </details>
    @endif
</section>
