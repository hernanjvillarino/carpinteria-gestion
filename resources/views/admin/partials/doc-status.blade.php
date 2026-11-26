{{-- Estado de un documento: $doc (EmployeeDocument|null). --}}
@php($status = $doc?->status() ?? \App\Enums\DocumentStatus::Falta)
<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $status->badgeClass() }}"
      @if ($doc?->expires_on) title="Vence el {{ $doc->expires_on->format('d/m/Y') }}" @endif>
    {{ $status->label() }}@if ($doc?->expires_on && $status !== \App\Enums\DocumentStatus::Vigente) · {{ $doc->expires_on->format('d/m/Y') }}@endif
</span>
