<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Datos del campeonato</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-xl space-y-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>
            @endif

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('campeonatos.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    @include('campeonatos._form')

                    <div class="flex items-center justify-between gap-4 pt-2">
                        @if ($liga->publica)
                            <a href="{{ route('campeonatos.publico', $liga) }}" target="_blank"
                               class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Ver página pública ↗</a>
                        @else
                            <span></span>
                        @endif
                        <div class="flex items-center gap-4">
                            <a href="{{ route('configuracion.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Volver</a>
                            <x-primary-button>Guardar</x-primary-button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Lista de asistencia por QR --}}
            <div class="bg-white p-6 shadow-sm sm:rounded-lg" x-data="{ pedir: {{ $liga->pedir_lista ? 'true' : 'false' }} }">
                <h3 class="text-base font-medium text-gray-900">📋 Lista de asistencia</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Cada fecha tiene un QR que los jugadores escanean en la cancha para quedar presentes.
                    Lo muestran el árbitro, los DT o la organización, y se puede imprimir para la mesa.
                </p>
                <form method="POST" action="{{ route('campeonatos.lista') }}" class="mt-4 space-y-3">
                    @csrf
                    @method('PUT')
                    <label class="flex items-start gap-3 text-sm">
                        <input type="checkbox" name="pedir_lista" value="1" x-model="pedir"
                               class="mt-0.5 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <span><span class="font-medium text-gray-900">Pedir lista</span> en los partidos de este campeonato</span>
                    </label>
                    <label class="flex items-start gap-3 text-sm" :class="pedir ? '' : 'opacity-50'">
                        <input type="checkbox" name="lista_obligatoria" value="1" @checked($liga->lista_obligatoria) :disabled="! pedir"
                               class="mt-0.5 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <span>
                            <span class="font-medium text-gray-900">Si no presenta la asistencia, no puede jugar</span>
                            <span class="block text-xs text-gray-500">Quien no esté en la lista no aparece para goles ni tarjetas. El DT, el árbitro o la organización pueden marcarlo a mano.</span>
                        </span>
                    </label>
                    <div class="flex justify-end">
                        <x-primary-button>Guardar lista</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
