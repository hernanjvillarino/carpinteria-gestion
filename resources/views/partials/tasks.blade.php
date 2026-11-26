@php($pending = $order->tasks->whereNull('done_at')->count())
@if ($order->tasks->isNotEmpty() || $isAdmin)
    <section class="card">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-semibold">Indicaciones y tareas</h2>
            @if ($order->tasks->isNotEmpty())
                <span class="text-sm text-stone-500">{{ $order->tasks->count() - $pending }} de {{ $order->tasks->count() }} hechas</span>
            @endif
        </div>
        <ul class="divide-y divide-stone-100">
            @forelse ($order->tasks as $task)
                <li class="flex items-start gap-3 py-2">
                    <form method="POST" action="{{ route('trabajos.tareas.toggle', [$order, $task]) }}">
                        @csrf @method('PATCH')
                        <button class="mt-0.5 flex h-6 w-6 items-center justify-center rounded border {{ $task->isDone() ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-stone-300 bg-white' }}"
                                title="{{ $task->isDone() ? 'Marcar como pendiente' : 'Marcar como hecha' }}" aria-label="{{ $task->isDone() ? 'Marcar como pendiente' : 'Marcar como hecha' }}: {{ $task->text }}">
                            @if ($task->isDone())<svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.6l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>@endif
                        </button>
                    </form>
                    <div class="flex-1 text-sm">
                        <p @class(['text-stone-400 line-through' => $task->isDone()])>{{ $task->text }}</p>
                        @if ($task->isDone())
                            <p class="text-xs text-stone-500">Hecha por {{ $task->doneBy?->name ?? '—' }} · {{ $task->done_at->format('d/m H:i') }}</p>
                        @endif
                    </div>
                    @if ($isAdmin)
                        <form method="POST" action="{{ route('trabajos.tareas.destroy', [$order, $task]) }}">
                            @csrf @method('DELETE')
                            <button class="text-xs text-stone-400 hover:text-red-700" title="Quitar">Quitar</button>
                        </form>
                    @endif
                </li>
            @empty
                <li class="py-2 text-sm text-stone-500">Este trabajo no tiene indicaciones para los carpinteros.</li>
            @endforelse
        </ul>

        @if ($isAdmin)
            @php($used = $order->tasks->pluck('instruction_template_id')->filter()->all())
            @php($available = $instructionTemplates->whereNotIn('id', $used))
            <details class="mt-3">
                <summary class="cursor-pointer text-sm text-madera-700">Agregar indicaciones</summary>
                <form method="POST" action="{{ route('trabajos.tareas.store', $order) }}" class="mt-3 space-y-3">
                    @csrf
                    @if ($available->isNotEmpty())
                        <div class="grid gap-1 text-sm sm:grid-cols-2">
                            @foreach ($available as $template)
                                <label class="flex items-start gap-2"><input class="mt-1" type="checkbox" name="instrucciones[]" value="{{ $template->id }}"> {{ $template->text }}</label>
                            @endforeach
                        </div>
                    @endif
                    <textarea class="input" name="instrucciones_extra" rows="2" placeholder="Otras indicaciones, una por línea"></textarea>
                    <div class="flex items-center gap-3">
                        <button class="btn">Agregar</button>
                        <a href="{{ route('admin.indicaciones.index') }}" class="text-xs text-stone-500 underline">Editar mi lista de indicaciones</a>
                    </div>
                </form>
            </details>
        @endif
    </section>
@endif
