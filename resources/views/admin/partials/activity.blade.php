@forelse ($recentUpdates as $update)
    <a href="{{ route('admin.trabajos.show', $update->workOrder) }}" class="block border-b border-stone-100 py-2 text-sm last:border-0">
        <span class="font-medium">{{ $update->workOrder->code }}</span>
        · {{ $update->user->name }}
        @if ($update->toStage) pasó a <span class="badge">{{ $update->toStage->name }}</span> @endif
        @if ($update->photos_count) · {{ $update->photos_count }} foto(s) @endif
        @if ($update->comment) <span class="block text-stone-600">“{{ str($update->comment)->limit(100) }}”</span> @endif
        <span class="block text-xs text-stone-500">{{ $update->created_at->diffForHumans() }}</span>
    </a>
@empty
    <p class="text-sm text-stone-500">Todavía no hay avances.</p>
@endforelse
