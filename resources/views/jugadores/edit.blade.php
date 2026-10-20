<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Editar jugador</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-xl sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>
            @endif
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <p class="mb-4 text-sm text-gray-500">Equipo: {{ $jugador->equipo->nombre }}</p>

                <form method="POST" action="{{ route('jugadores.update', $jugador) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="nombre" value="Nombre" />
                        <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full"
                                      :value="old('nombre', $jugador->nombre)" required autofocus />
                        <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="dorsal" value="Número de camiseta (opcional)" />
                        <x-text-input id="dorsal" name="dorsal" type="number" min="1" max="99"
                                      class="mt-1 block w-full" :value="old('dorsal', $jugador->dorsal)" />
                        <x-input-error :messages="$errors->get('dorsal')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="posicion" value="Posición (opcional)" />
                        <x-text-input id="posicion" name="posicion" type="text" class="mt-1 block w-full"
                                      :value="old('posicion', $jugador->posicion)" />
                        <x-input-error :messages="$errors->get('posicion')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="foto" value="Foto (opcional)" />
                        @if ($jugador->foto)
                            <img src="{{ asset($jugador->foto) }}?t={{ $jugador->updated_at?->timestamp }}"
                                 alt="Foto actual" class="mb-2 h-20 w-20 rounded-full object-cover border border-gray-200">
                        @endif
                        <input id="foto" name="foto" type="file" accept="image/*"
                               class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100">
                        <x-input-error :messages="$errors->get('foto')" class="mt-2" />
                        <p class="mt-1 text-xs text-gray-400">Subir una foto nueva reemplaza la anterior. Hasta 2 MB.</p>
                    </div>

                    <div class="flex items-center justify-end gap-4">
                        <a href="{{ route('equipos.show', $jugador->equipo_id) }}" class="text-sm text-gray-600 hover:text-gray-900">Cancelar</a>
                        <x-primary-button>Guardar cambios</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
