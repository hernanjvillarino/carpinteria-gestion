<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingresar · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-madera-50 px-4 font-sans text-stone-800">
    <div class="w-full max-w-sm">
        <h1 class="mb-1 text-center text-2xl font-semibold text-madera-700">{{ config('app.name') }}</h1>
        <p class="mb-6 text-center text-sm text-stone-500">Ingresá con tu usuario para ver tus trabajos o el estado de tu pedido.</p>
        <form method="POST" action="{{ route('login') }}" class="card space-y-4">
            @csrf
            <div>
                <label for="email" class="label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="input">
                @error('email')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="label">Contraseña</label>
                <input id="password" name="password" type="password" required autocomplete="current-password" class="input">
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1"> Recordarme</label>
            <button class="btn-primary w-full">Ingresar</button>
        </form>
    </div>
</body>
</html>
