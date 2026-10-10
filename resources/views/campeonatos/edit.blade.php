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
        </div>
    </div>
</x-app-layout>
