<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @include('layouts._fuentes')

        @include('layouts._pwa')
        @include('layouts._analitica')

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        @php
            // Menú lateral en pantallas grandes para quien tiene muchas opciones (hoy: super admin).
            $menuLateral = (bool) auth()->user()?->is_super_admin;
        @endphp
        @if ($menuLateral)
            @include('layouts.sidebar')
        @endif
        <div class="flex min-h-screen flex-col bg-gray-100 dark:bg-gray-900 {{ $menuLateral ? 'lg:pl-64' : '' }}">
            <div class="{{ $menuLateral ? 'lg:hidden' : '' }}">
                @include('layouts.navigation')
            </div>

            <!-- Page Heading -->
            @isset($header)
                <header class="encabezado-pagina bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            @include('logros._aviso')

            <!-- Page Content -->
            <main class="flex-1">
                {{ $slot }}
            </main>

            <x-footer-cancha />
        </div>
    </body>
</html>
