<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Lista · Fecha {{ $numero }} <span class="text-base font-normal text-gray-500">· {{ $torneo->nombre }}</span>
        </h2>
    </x-slot>

    @php($yo = auth()->user())

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>
            @endif

            @unless ($liga->pedir_lista)
                <div class="rounded-md bg-amber-50 p-4 text-sm text-amber-800">
                    La lista de asistencia está <strong>desactivada</strong> en este campeonato: el QR no marca a nadie.
                    @if ($yo->hasRole('admin'))
                        <a href="{{ route('campeonatos.edit') }}" class="font-medium underline">Activarla</a>.
                    @endif
                </div>
            @endunless

            {{-- QR de la fecha --}}
            <div class="bg-white p-6 text-center shadow-sm sm:rounded-lg">
                <div class="mx-auto w-64 max-w-full [&>svg]:h-auto [&>svg]:w-full">{!! $qr !!}</div>
                <p class="mt-3 text-sm text-gray-700">Escanea con la cámara del celular para quedar <strong>presente</strong>.</p>
                <p class="mt-1 text-xs text-gray-400">
                    Sirve para todos los partidos de la fecha · abre el día del partido
                    @if ($liga->exigeLista()) · <span class="font-medium text-red-600">sin lista no se juega</span> @endif
                </p>
                <div class="mt-4 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('lista.imprimir', [$torneo, $numero]) }}" target="_blank"
                       class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">🖨️ Imprimir QR</a>
                    @if ($yo->hasRole('admin'))
                        <form method="POST" action="{{ route('lista.regenerar', [$torneo, $numero]) }}"
                              data-confirm="¿Generar un QR nuevo? El anterior (y el impreso) dejará de funcionar." onsubmit="return confirm(this.dataset.confirm)">
                            @csrf
                            <button type="submit" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Nuevo QR</button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Lista en vivo por partido --}}
            @foreach ($partidos as $partido)
                @php($asistencias = $partido->asistencias->keyBy('jugador_id'))
                <div class="bg-white p-5 shadow-sm sm:rounded-lg">
                    <div class="mb-4 flex flex-wrap items-baseline justify-between gap-2">
                        <a href="{{ route('partidos.show', $partido) }}" class="text-base font-medium text-gray-900 hover:text-indigo-700">
                            {{ $partido->equipoLocal->nombre }} vs {{ $partido->equipoVisita->nombre }}
                        </a>
                        <span class="text-xs text-gray-500">
                            {{ $partido->fecha_hora?->format('d/m H:i') ?? 'Sin horario' }}
                            · {{ \App\Support\Lista::abierta($partido) ? 'lista abierta' : 'lista cerrada' }}
                        </span>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @include('lista._equipo', ['equipo' => $partido->equipoLocal])
                        @include('lista._equipo', ['equipo' => $partido->equipoVisita])
                    </div>
                </div>
            @endforeach

            <p class="text-center text-xs text-gray-400">La lista se actualiza sola cada 20 segundos.</p>
        </div>
    </div>

    <script>
        setTimeout(() => location.reload(), 20000);
    </script>
</x-app-layout>
