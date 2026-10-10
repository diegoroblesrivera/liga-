<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Editar equipo</h2>
    </x-slot>

    @php($esAdmin = auth()->user()?->hasRole('admin'))

    <div class="py-8">
        <div class="mx-auto max-w-xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('equipos.update', $equipo) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="nombre" value="Nombre del equipo" />
                        @if ($esAdmin)
                            <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full"
                                          :value="old('nombre', $equipo->nombre)" required />
                            <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                        @else
                            <p class="mt-1 rounded-md bg-gray-50 px-3 py-2 text-sm text-gray-700">{{ $equipo->nombre }}</p>
                            <p class="mt-1 text-xs text-gray-400">Solo el administrador puede cambiar el nombre.</p>
                        @endif
                    </div>

                    <div>
                        <x-input-label for="modalidad_id" value="Modalidad" />
                        @if ($esAdmin)
                            <select id="modalidad_id" name="modalidad_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ($modalidades as $modalidad)
                                    <option value="{{ $modalidad->id }}" @selected(old('modalidad_id', $equipo->modalidad_id) == $modalidad->id)>
                                        {{ $modalidad->nombre }} (máx. {{ $modalidad->max_jugadores }} jugadores)
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('modalidad_id')" class="mt-2" />
                        @else
                            <p class="mt-1 rounded-md bg-gray-50 px-3 py-2 text-sm text-gray-700">{{ $equipo->modalidad->nombre }}</p>
                            <p class="mt-1 text-xs text-gray-400">Solo el administrador puede cambiar la modalidad.</p>
                        @endif
                    </div>

                    <div>
                        <x-input-label for="color_texto" value="Color de camiseta" />
                        <div class="mt-1 flex items-center gap-3">
                            <input id="color_selector" type="color"
                                   value="{{ old('color_camiseta', $equipo->color_camiseta ?: '#1e3a8a') }}"
                                   class="h-10 w-14 shrink-0 cursor-pointer rounded-md border border-gray-300">
                            <x-text-input id="color_texto" name="color_camiseta" type="text"
                                          class="block w-full font-mono uppercase"
                                          :value="old('color_camiseta', $equipo->color_camiseta ?: '#1e3a8a')"
                                          maxlength="30" placeholder="#1E3A8A" />
                        </div>
                        <x-input-error :messages="$errors->get('color_camiseta')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="escudo" value="Escudo / logo (opcional)" />
                        @if ($equipo->escudo)
                            <img src="{{ asset($equipo->escudo) }}?t={{ $equipo->updated_at?->timestamp }}"
                                 alt="Escudo actual" class="mb-2 h-16 w-16 rounded object-cover border border-gray-200">
                        @endif
                        <input id="escudo" name="escudo" type="file" accept="image/*"
                               class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100">
                        <x-input-error :messages="$errors->get('escudo')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="foto" value="Foto del plantel (opcional)" />
                        @if ($equipo->foto)
                            <img src="{{ asset($equipo->foto) }}?t={{ $equipo->updated_at?->timestamp }}"
                                 alt="Foto actual" class="mb-2 h-28 w-full rounded object-cover border border-gray-200">
                        @endif
                        <input id="foto" name="foto" type="file" accept="image/*"
                               class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100">
                        <x-input-error :messages="$errors->get('foto')" class="mt-2" />
                        <p class="mt-1 text-xs text-gray-400">Subir una imagen nueva reemplaza la anterior. Hasta 2 MB.</p>
                    </div>

                    <div class="flex items-center justify-end gap-4">
                        <a href="{{ route('equipos.show', $equipo) }}" class="text-sm text-gray-600 hover:text-gray-900">Cancelar</a>
                        <x-primary-button>Guardar cambios</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const selector = document.getElementById('color_selector');
            const texto = document.getElementById('color_texto');
            if (!selector || !texto) return;
            selector.addEventListener('input', () => { texto.value = selector.value.toUpperCase(); });
            texto.addEventListener('input', () => {
                const v = texto.value.trim();
                if (/^#([0-9A-Fa-f]{6})$/.test(v)) selector.value = v;
            });
        })();
    </script>
</x-app-layout>
