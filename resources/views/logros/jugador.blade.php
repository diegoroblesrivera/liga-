<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            @if ($jugador->foto)
                <img src="{{ asset($jugador->foto) }}?t={{ $jugador->updated_at?->timestamp }}" alt="{{ $jugador->nombre }}"
                     class="h-14 w-14 rounded-full border border-gray-200 object-cover">
            @else
                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-xl text-gray-400">{{ mb_substr($jugador->nombre, 0, 1) }}</span>
            @endif
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ $jugador->dorsal ? '#'.$jugador->dorsal.' ' : '' }}{{ $jugador->nombre }}
                </h2>
                <p class="text-sm text-gray-500">
                    <a href="{{ route('equipos.show', $jugador->equipo) }}" class="hover:text-gray-700">{{ $jugador->equipo->nombre }}</a>
                    · {{ $liga->nombre }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-4 sm:px-6 lg:px-8">
            <div class="bg-white p-5 shadow-sm sm:rounded-lg">
                <h3 class="mb-4 text-base font-medium text-gray-900">🏅 Logros</h3>
                @include('logros._grilla', ['grilla' => $grilla])
            </div>

            @if (! $liga->esPro() && auth()->user()->hasRole('admin'))
                <a href="{{ route('campeonatos.plan') }}" class="block rounded-lg bg-amber-50 p-4 text-sm text-amber-800 hover:bg-amber-100">
                    ⭐ Con el plan <strong>Pro</strong> tus jugadores también ganan Campeón, Pichichi, Racha goleadora y más. Ver el plan →
                </a>
            @endif

            @if ($esMio)
                <p class="text-center text-sm"><a href="{{ route('logros.mios') }}" class="font-medium text-indigo-600 hover:text-indigo-800">Ver todos mis logros →</a></p>
            @endif
        </div>
    </div>
</x-app-layout>
