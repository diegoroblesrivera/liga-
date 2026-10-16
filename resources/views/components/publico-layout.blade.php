<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'Liga') }}</title>
    @include('layouts._fuentes')
    @include('layouts._pwa')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-gray-100 font-sans text-gray-900 antialiased">
    <header class="bg-balon">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
            <a href="{{ url('/') }}" aria-label="{{ config('app.name') }}">
                <x-logo class="text-xl" />
            </a>
            <nav class="text-sm">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-md bg-cancha-400 px-3 py-1.5 font-semibold text-balon hover:bg-cancha-300">
                        Ir al panel
                    </a>
                @else
                    <a href="{{ route('login') }}" class="rounded-md bg-cancha-400 px-3 py-1.5 font-semibold text-balon hover:bg-cancha-300">
                        Iniciar sesión
                    </a>
                @endauth
            </nav>
        </div>
        <div class="franjas-cesped h-1.5"></div>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8">
        {{ $slot }}
    </main>

    <x-footer-cancha />
</body>
</html>
