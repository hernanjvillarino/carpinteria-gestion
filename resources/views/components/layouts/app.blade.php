@props(['title' => null])
@php($user = auth()->user())
@php($internal = $user->role !== \App\Enums\Role::Cliente)
@php($unread = $internal ? $user->unreadNotifications()->count() : 0)
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="device-endpoint" content="{{ route('push.device.store') }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#6f4520">
    <link rel="icon" href="/icons/icon-192.png" type="image/png">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Carpintería">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-madera-50 font-sans text-stone-800 antialiased">
    <header class="border-b border-stone-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3">
            <a href="{{ route('panel') }}" class="font-semibold text-madera-700">{{ config('app.name') }}</a>
            <nav class="flex flex-1 flex-wrap gap-4 text-sm">
                @if ($user->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" @class(['font-semibold text-madera-700' => request()->routeIs('admin.dashboard')])>Inicio</a>
                    <a href="{{ route('admin.trabajos.index') }}" @class(['font-semibold text-madera-700' => request()->routeIs('admin.trabajos.*')])>Trabajos</a>
                    <a href="{{ route('admin.clientes.index') }}" @class(['font-semibold text-madera-700' => request()->routeIs('admin.clientes.*')])>Clientes</a>
                    <a href="{{ route('admin.stock.index') }}" @class(['font-semibold text-madera-700' => request()->routeIs('admin.stock.*', 'admin.proveedores.*', 'admin.presupuestos.*')])>Stock</a>
                    <a href="{{ route('admin.barrios.index') }}" @class(['font-semibold text-madera-700' => request()->routeIs('admin.barrios.*', 'admin.personal.*', 'admin.documentacion.*')])>Barrios</a>
                    <a href="{{ route('admin.usuarios.index') }}" @class(['font-semibold text-madera-700' => request()->routeIs('admin.usuarios.*', 'admin.indicaciones.*')])>Equipo</a>
                @elseif ($user->role === \App\Enums\Role::Carpintero)
                    <a href="{{ route('taller.index') }}" class="font-semibold text-madera-700">Mis trabajos</a>
                @else
                    <a href="{{ route('portal.index') }}" class="font-semibold text-madera-700">Mis pedidos</a>
                @endif
            </nav>
            <div class="flex items-center gap-3 text-sm">
                @if ($internal)
                    <a href="{{ route('avisos.index') }}" class="relative rounded-full p-1.5 text-stone-600 hover:bg-stone-100" title="Avisos" data-unread-count="{{ route('avisos.count') }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                        <span class="sr-only">Avisos</span>
                        <span data-badge @if (! $unread) hidden @endif class="absolute -top-0.5 -right-0.5 min-w-[1.1rem] rounded-full bg-red-600 px-1 text-center text-[10px] leading-[1.1rem] font-semibold text-white">{{ $unread > 99 ? '99+' : $unread }}</span>
                    </a>
                @endif
                <span class="text-stone-500">{{ $user->name }}</span>
                <form method="POST" action="{{ route('logout') }}" data-logout>@csrf<button class="text-stone-500 underline hover:text-stone-800">Salir</button></form>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-6">
        @if (session('status'))
            <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">{{ session('status') }}</div>
        @endif
        @if ($title)
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl font-semibold text-stone-900">{{ $title }}</h1>
                {{ $actions ?? '' }}
            </div>
        @endif
        {{ $slot }}
    </main>
</body>
</html>
