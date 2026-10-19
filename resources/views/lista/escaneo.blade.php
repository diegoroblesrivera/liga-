<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Lista de asistencia</h2>
    </x-slot>

    @php
        $mensajes = [
            'ok' => ['✅', '¡Quedaste presente!', 'bg-green-50 text-green-800'],
            'ya' => ['✅', 'Ya estabas en la lista.', 'bg-green-50 text-green-800'],
            'otro_dia' => ['🗓️', 'La lista se abre el día del partido.', 'bg-amber-50 text-amber-800'],
            'cerrada' => ['🔒', 'La lista de este partido ya está cerrada.', 'bg-gray-50 text-gray-700'],
            'no_inscrito' => ['🤔', 'No estás inscrito en un equipo que juegue esta fecha.', 'bg-amber-50 text-amber-800'],
            'desactivada' => ['⏸️', 'Este campeonato no está pidiendo lista.', 'bg-gray-50 text-gray-700'],
        ];
        [$icono, $titulo, $clases] = $mensajes[$estado];
    @endphp

    <div class="py-10">
        <div class="mx-auto max-w-md px-4">
            <div class="rounded-lg p-8 text-center shadow-sm {{ $clases }}">
                <div class="text-6xl">{{ $icono }}</div>
                <h3 class="mt-4 text-xl font-semibold">{{ $titulo }}</h3>
                <p class="mt-2 text-sm">{{ $liga->nombre }} · Fecha {{ $numero }}</p>
                @isset($partido)
                    <p class="mt-1 text-sm font-medium">
                        {{ $partido->equipoLocal->nombre }} vs {{ $partido->equipoVisita->nombre }}
                        @if ($partido->fecha_hora) · {{ $partido->fecha_hora->format('d/m H:i') }} @endif
                    </p>
                @endisset
                @isset($jugador)
                    <p class="mt-3 text-xs opacity-80">Como {{ $jugador->nombre }}</p>
                @endisset
                @if ($estado === 'no_inscrito')
                    <p class="mt-3 text-xs opacity-80">Pídele a tu DT el link de invitación de tu equipo y vuelve a escanear.</p>
                @endif
            </div>
            <div class="mt-6 text-center">
                <a href="{{ route('dashboard') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Ir al inicio</a>
            </div>
        </div>
    </div>
</x-app-layout>
