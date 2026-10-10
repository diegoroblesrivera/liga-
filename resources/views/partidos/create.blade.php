<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Programar partido</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('partidos.store') }}" class="space-y-4">
                    @csrf
                    @include('partidos._form')

                    <div class="flex items-center justify-end gap-4 pt-2">
                        <a href="{{ route('partidos.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancelar</a>
                        <x-primary-button>Programar partido</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
