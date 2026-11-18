@props(['title' => null])
@php($user = auth()->user())
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
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
                    <a href="{{ route('admin.barrios.index') }}" @class(['font-semibold text-madera-700' => request()->routeIs('admin.barrios.*')])>Barrios</a>
                    <a href="{{ route('admin.usuarios.index') }}" @class(['font-semibold text-madera-700' => request()->routeIs('admin.usuarios.*')])>Equipo</a>
                @elseif ($user->role === \App\Enums\Role::Carpintero)
                    <a href="{{ route('taller.index') }}" class="font-semibold text-madera-700">Mis trabajos</a>
                @else
                    <a href="{{ route('portal.index') }}" class="font-semibold text-madera-700">Mis pedidos</a>
                @endif
            </nav>
            <div class="flex items-center gap-3 text-sm">
                <span class="text-stone-500">{{ $user->name }}</span>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-stone-500 underline hover:text-stone-800">Salir</button></form>
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
