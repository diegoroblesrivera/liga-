<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Reprogramar partido</h2>

            <a href="{{ route('partidos.index') }}#fecha-{{ $partido->numero_fecha }}"
               class="inline-block px-5 py-1.5 border border-[#19140035] hover:border-[#1915014a] text-[#1b1b18] rounded-sm text-sm leading-normal">
                Volver a la fecha {{ $partido->numero_fecha }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('partidos.update', $partido) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    @include('partidos._form')

                    <div class="flex items-center justify-end gap-4 pt-2">
                        <a href="{{ route('partidos.show', $partido) }}" class="text-sm text-gray-600 hover:text-gray-900">Cancelar</a>
                        <x-primary-button>Guardar cambios</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>