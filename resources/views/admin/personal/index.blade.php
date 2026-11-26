<x-layouts.app title="Personal y documentación">
    <x-slot:actions>
        <a href="{{ route('admin.documentacion.index') }}" class="btn">Tipos de documento</a>
        <a href="{{ route('admin.personal.create') }}" class="btn-primary">Nuevo empleado</a>
    </x-slot:actions>

    @if ($alerts->isNotEmpty())
        <section class="card mb-6 border-amber-200 bg-amber-50">
            <h2 class="mb-2 font-semibold text-amber-900">Vencimientos para atender</h2>
            <ul class="space-y-1 text-sm">
                @foreach ($alerts as $doc)
                    <li class="flex flex-wrap items-center gap-2">
                        @include('admin.partials.doc-status', ['doc' => $doc])
                        <a href="{{ route('admin.personal.show', $doc->employee_id) }}" class="font-medium hover:underline">{{ $employees->firstWhere('id', $doc->employee_id)->name }}</a>
                        <span class="text-stone-600">{{ $doc->type->displayName() }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="card overflow-x-auto !p-0">
        <table class="table">
            <thead><tr><th>Nombre</th><th>DNI</th><th>Acceso al sistema</th><th>Documentos</th><th>Barrios habilitados</th><th></th></tr></thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($employees as $employee)
                    @php($current = $employee->currentDocuments())
                    @php($bad = $current->filter(fn ($d) => ! $d->status()->allowsEntry())->count())
                    @php($soon = $current->filter(fn ($d) => $d->status() === \App\Enums\DocumentStatus::PorVencer)->count())
                    <tr @class(['opacity-60' => ! $employee->is_active])>
                        <td class="font-medium"><a href="{{ route('admin.personal.show', $employee) }}" class="hover:underline">{{ $employee->name }}</a>@unless ($employee->is_active) <span class="badge">Inactivo</span>@endunless</td>
                        <td>{{ $employee->dni }}</td>
                        <td>{{ $employee->user?->role->label() ?? '—' }}</td>
                        <td>
                            {{ $current->count() }}
                            @if ($bad)<span class="badge !bg-red-100 !text-red-800">{{ $bad }} vencido(s)</span>@endif
                            @if ($soon)<span class="badge !bg-amber-100 !text-amber-800">{{ $soon }} por vencer</span>@endif
                        </td>
                        <td>
                            @if ($barrios->isEmpty())
                                <span class="text-stone-400">—</span>
                            @else
                                {{ $barrios->filter(fn ($b) => $employee->isEnabledFor($b))->count() }} de {{ $barrios->count() }}
                            @endif
                        </td>
                        <td class="text-right"><a href="{{ route('admin.personal.show', $employee) }}" class="text-madera-700 underline">Documentación</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-stone-500">Todavía no hay personal cargado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="mt-3 text-sm">
        @if (request()->boolean('inactivos'))
            <a href="{{ route('admin.personal.index') }}" class="text-madera-700 underline">Ver solo el personal activo</a>
        @else
            <a href="{{ route('admin.personal.index', ['inactivos' => 1]) }}" class="text-madera-700 underline">Ver también el personal inactivo</a>
        @endif
    </p>
</x-layouts.app>
