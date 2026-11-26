<x-layouts.app title="Avisos">
    <x-slot:actions>
        @if (auth()->user()->unreadNotifications()->exists())
            <form method="POST" action="{{ route('avisos.read-all') }}">@csrf<button class="btn">Marcar todo como leído</button></form>
        @endif
    </x-slot:actions>

    <section class="card mb-6 flex flex-wrap items-center justify-between gap-3 text-sm" data-push data-vapid-key="{{ config('webpush.vapid.public_key') }}" data-endpoint="{{ route('push.store') }}">
        <div>
            <p class="font-medium">Avisos en este dispositivo</p>
            <p class="text-stone-600" data-push-status>Para recibir los avisos en el celular aunque la app esté cerrada, activalos acá.</p>
        </div>
        <button type="button" class="btn-primary" hidden>Activar avisos</button>
    </section>

    <div class="card !p-0">
        <ul class="divide-y divide-stone-100">
            @forelse ($notifications as $notification)
                <li>
                    <a href="{{ route('avisos.open', $notification->id) }}" @class(['flex gap-3 px-4 py-3 hover:bg-stone-50', 'bg-madera-50' => ! $notification->read_at])>
                        <span @class(['mt-1.5 h-2 w-2 shrink-0 rounded-full', 'bg-madera-600' => ! $notification->read_at, 'bg-transparent' => $notification->read_at])></span>
                        <span class="flex-1 text-sm">
                            <span @class(['block', 'font-semibold' => ! $notification->read_at])>{{ $notification->data['title'] ?? 'Aviso' }}</span>
                            @if (! empty($notification->data['body']))<span class="block text-stone-600">{{ $notification->data['body'] }}</span>@endif
                            <span class="block text-xs text-stone-500">{{ $notification->created_at->diffForHumans() }}</span>
                        </span>
                    </a>
                </li>
            @empty
                <li class="px-4 py-6 text-center text-sm text-stone-500">No tenés avisos.</li>
            @endforelse
        </ul>
    </div>
    <div class="mt-4">{{ $notifications->links() }}</div>
</x-layouts.app>
