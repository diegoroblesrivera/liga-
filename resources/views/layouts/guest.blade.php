<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @include('layouts._fuentes')

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="franjas-cesped flex min-h-screen flex-col items-center px-4 pt-8 sm:justify-center sm:pt-0">
            <a href="/" class="rounded-xl border-4 border-white bg-balon px-6 py-3 shadow-lg">
                <span class="titulo-deportivo text-3xl text-white">⚽ {{ config('app.name') }}</span>
            </a>

            <div class="mt-6 w-full overflow-hidden rounded-xl border-4 border-white bg-white px-6 py-5 shadow-xl sm:max-w-md">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
