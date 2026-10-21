<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Tabla de posiciones</h2>
    </x-slot>



    <div class="py-8">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">

            @include('tablas._selector')

            @if (! $torneo)
                <div class="bg-white p-6 text-sm text-gray-500 shadow-sm sm:rounded-lg">
                    No hay torneos todavía.
                </div>
            @else
                @if ($segunda && auth()->user()?->hasRole('admin'))
                    <form method="POST" action="{{ route('fixtures.segunda', $torneo) }}" class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-cancha-50 p-4 ring-1 ring-cancha-200"
                          data-confirm="¿Generar la eliminación directa con los {{ $segunda->clasifican }} clasificados?" onsubmit="return confirm(this.dataset.confirm)">
                        @csrf
                        <p class="text-sm text-gray-800">🏁 Terminó la primera etapa. Ya puedes generar la <strong>eliminación directa</strong> con los {{ $segunda->clasifican }} clasificados.</p>
                        <x-primary-button>Generar eliminatoria</x-primary-button>
                    </form>
                @endif
                @error('torneo')
                    <div class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">{{ $message }}</div>
                @enderror
                @if (session('status'))
                    <div class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-700">{{ session('status') }}</div>
                @endif

                <div class="space-y-6">
                    @include('tablas._secciones', ['secciones' => $secciones])
                </div>

                <p class="mt-3 text-xs text-gray-400">
                    PJ jugados · PG ganados · PE empatados · PP perdidos · GF goles a favor · GC en contra · Dif diferencia · Pts puntos.
                    Desliza la tabla hacia la derecha para ver todas las columnas.
                </p>
            @endif

        </div>
    </div>
</x-app-layout>
