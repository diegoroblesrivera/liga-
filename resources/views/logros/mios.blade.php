<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">🏅 Mis logros</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">

            @foreach ($fichas as $f)
                <div class="bg-white p-5 shadow-sm sm:rounded-lg">
                    <div class="mb-4">
                        <h3 class="text-base font-medium text-gray-900">🏆 {{ $f['liga']->nombre }}</h3>
                        <p class="text-sm text-gray-500">
                            {{ $f['equipo']->nombre }}{{ $f['ficha']->dorsal ? ' · #'.$f['ficha']->dorsal : '' }}
                            @if ($f['liga']->esPro()) · <span class="font-medium text-cancha-700">Campeonato Pro</span> @endif
                        </p>
                    </div>
                    @include('logros._grilla', ['grilla' => $f['grilla']])
                </div>
            @endforeach

            <div class="bg-white p-5 shadow-sm sm:rounded-lg">
                <h3 class="mb-4 text-base font-medium text-gray-900">⚽ Pichangas</h3>
                @include('logros._grilla', ['grilla' => $pichangas])
            </div>

            @if ($fichas->isEmpty())
                <p class="text-center text-sm text-gray-500">
                    Cuando juegues en un campeonato de {{ config('app.name') }} vas a ver acá tus logros: goles, partidos, hat-tricks y más.
                </p>
            @endif
        </div>
    </div>
</x-app-layout>
