<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Editar torneo</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('torneos.update', $torneo) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    @include('torneos._form')

                    <div class="flex items-center justify-end gap-4 pt-2">
                        <a href="{{ route('torneos.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancelar</a>
                        <x-primary-button>Guardar cambios</x-primary-button>
                    </div>
                </form>
            </div>

            {{-- Cierre del torneo: entrega de logros --}}
            <div class="mt-4 bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-base font-medium text-gray-900">🏆 Cierre del torneo</h3>
                @if ($errors->has('torneo'))
                    <div class="mt-3 rounded-md bg-red-50 p-3 text-sm text-red-700">{{ $errors->first('torneo') }}</div>
                @endif
                @if (session('status'))
                    <div class="mt-3 rounded-md bg-green-50 p-3 text-sm text-green-700">{{ session('status') }}</div>
                @endif
                @if ($torneo->cerrado_en)
                    <p class="mt-1 text-sm text-gray-600">Cerrado el {{ $torneo->cerrado_en->format('d/m/Y') }}. Los logros de campeón, goleador y asistencia perfecta ya se entregaron.</p>
                    <form method="POST" action="{{ route('torneos.reabrir', $torneo) }}" class="mt-4"
                          data-confirm="¿Reabrir el torneo? Se quitan los logros de cierre hasta que lo vuelvas a cerrar." onsubmit="return confirm(this.dataset.confirm)">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800">Reabrir torneo</button>
                    </form>
                @else
                    <p class="mt-1 text-sm text-gray-600">
                        Cuando se jueguen todos los partidos, ciérralo para entregar los logros de <strong>Campeón</strong>,
                        <strong>Pichichi</strong> y <strong>Asistencia perfecta</strong> (solo en campeonatos Pro).
                    </p>
                    <form method="POST" action="{{ route('torneos.cerrar', $torneo) }}" class="mt-4"
                          data-confirm="¿Cerrar el torneo y entregar los logros?" onsubmit="return confirm(this.dataset.confirm)">
                        @csrf
                        <x-primary-button>Cerrar torneo</x-primary-button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
