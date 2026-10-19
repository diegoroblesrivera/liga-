<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                @if ($equipo->escudo)
                    <img src="{{ asset($equipo->escudo) }}?t={{ $equipo->updated_at?->timestamp }}"
                         alt="Escudo" class="h-9 w-9 rounded object-cover border border-gray-200">
                @endif
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ $equipo->nombre }}</h2>
            </div>

            @can('update', $equipo)
                <a href="{{ route('equipos.edit', $equipo) }}"
                   class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Editar equipo
                </a>
                <a href="{{ route('equipos.alineacion', $equipo) }}"
                   class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Ver alineación
                </a>
            @endcan
        </div>
    </x-slot>

    @php($yo = auth()->user())

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            {{-- Datos del equipo --}}
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-4">
                    <div>
                        <dt class="text-gray-500">Modalidad</dt>
                        <dd class="text-gray-900">{{ $equipo->modalidad->nombre }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Cupo</dt>
                        <dd class="text-gray-900">
                            {{ $equipo->jugadores->count() }} / {{ $equipo->modalidad->max_jugadores }} jugadores
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Director Técnico</dt>
                        <dd class="text-gray-900">{{ $equipo->dt?->name ?? 'Sin DT asignado' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Color de camiseta</dt>
                        <dd class="mt-1 flex items-center gap-2">
                            @if ($equipo->color_camiseta)
                                <span class="inline-block h-5 w-5 rounded-full border border-gray-300"
                                      style="background-color: {{ $equipo->color_camiseta }};"></span>
                                <span class="text-gray-900">{{ $equipo->color_camiseta }}</span>
                            @else
                                <span class="text-gray-500">Sin definir</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Foto del plantel --}}
            @if ($equipo->foto)
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <img src="{{ asset($equipo->foto) }}?t={{ $equipo->updated_at?->timestamp }}"
                         alt="Plantel de {{ $equipo->nombre }}" class="w-full object-cover">
                </div>
            @endif

            {{-- Link recién generado --}}
            @if (session('link_invitacion'))
                <div class="rounded-md bg-indigo-50 p-4">
                    <p class="mb-2 text-sm font-medium text-indigo-800">Link generado — cópialo y compártelo:</p>
                    <div class="flex gap-2">
                        <input id="link-invitacion" type="text" readonly
                               value="{{ session('link_invitacion') }}"
                               class="w-full rounded-md border-gray-300 bg-white text-sm text-gray-700">
                        <button type="button" onclick="copiarLink()"
                                class="shrink-0 rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Copiar
                        </button>
                    </div>
                </div>
            @endif

            {{-- Generación de invitaciones --}}
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="mb-4 text-base font-medium text-gray-900">Invitaciones</h3>
                <div class="flex flex-wrap gap-3">
                    @can('create', [App\Models\Invitacion::class, $equipo, 'dt'])
                        <form method="POST" action="{{ route('invitaciones.store', $equipo) }}">
                            @csrf
                            <input type="hidden" name="tipo" value="dt">
                            <x-primary-button>Generar link de DT</x-primary-button>
                        </form>
                    @endcan

                    @can('create', [App\Models\Invitacion::class, $equipo, 'jugador'])
                        <form method="POST" action="{{ route('invitaciones.store', $equipo) }}">
                            @csrf
                            <input type="hidden" name="tipo" value="jugador">
                            <x-primary-button>Generar link de jugadores</x-primary-button>
                        </form>
                    @endcan

                    @cannot('create', [App\Models\Invitacion::class, $equipo, 'jugador'])
                        @cannot('create', [App\Models\Invitacion::class, $equipo, 'dt'])
                            <p class="text-sm text-gray-500">No tienes permisos para generar invitaciones de este equipo.</p>
                        @endcannot
                    @endcannot
                </div>
            </div>

            {{-- Plantel con edición y baja --}}
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="mb-4 text-base font-medium text-gray-900">
                    Jugadores ({{ $equipo->jugadores->count() }})
                </h3>
                @forelse ($equipo->jugadores as $jugador)
                    <div class="flex items-center justify-between border-b border-gray-100 py-2 text-sm last:border-0">
                        <span class="flex items-center gap-3 text-gray-900">
                            @if ($jugador->foto)
                                <img src="{{ asset($jugador->foto) }}?t={{ $jugador->updated_at?->timestamp }}"
                                     alt="{{ $jugador->nombre }}" class="h-8 w-8 rounded-full object-cover border border-gray-200">
                            @else
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-xs text-gray-400">
                                    {{ mb_substr($jugador->nombre, 0, 1) }}
                                </span>
                            @endif
                            <span>
                                @if ($jugador->dorsal)
                                    <span class="mr-1 text-gray-400">#{{ $jugador->dorsal }}</span>
                                @endif
                                <a href="{{ route('jugadores.logros', $jugador) }}" class="hover:text-indigo-700">{{ $jugador->nombre }}</a>
                                @include('logros._mini', ['iconos' => $insignias->get($jugador->id)])
                            </span>
                        </span>
                        <div class="flex items-center gap-3">
                            @if ($jugador->posicion)
                                <span class="text-gray-500">{{ $jugador->posicion }}</span>
                            @endif
                            @can('update', $jugador)
                                <a href="{{ route('jugadores.edit', $jugador) }}"
                                   class="text-xs text-indigo-600 hover:text-indigo-800">Editar</a>
                            @endcan
                            @can('delete', $jugador)
                                <form method="POST" action="{{ route('jugadores.destroy', $jugador) }}"
                                      onsubmit="return confirm('¿Dar de baja a {{ $jugador->nombre }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600 hover:text-red-800">Baja</button>
                                </form>
                            @endcan
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Aún no hay jugadores inscritos.</p>
                @endforelse
            </div>

            {{-- Sumarme como jugador: solo el DT de este equipo sin ficha --}}
            @if ($yo && $yo->equipo_id === $equipo->id && ! $yo->jugador)
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="mb-4 text-base font-medium text-gray-900">Sumarme como jugador</h3>
                    <form method="POST" action="{{ route('jugadores.unirme', $equipo) }}"
                          class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:items-end">
                        @csrf
                        <div>
                            <x-input-label for="dorsal" value="Número (opcional)" />
                            <x-text-input id="dorsal" name="dorsal" type="number" min="1" max="99"
                                          class="mt-1 block w-full" :value="old('dorsal')" />
                        </div>
                        <div>
                            <x-input-label for="posicion" value="Posición (opcional)" />
                            <x-text-input id="posicion" name="posicion" type="text" class="mt-1 block w-full"
                                          :value="old('posicion')" />
                        </div>
                        <div class="flex justify-end">
                            <x-primary-button>Sumarme</x-primary-button>
                        </div>
                    </form>
                    <p class="mt-2 text-xs text-gray-400">Te agregarás al plantel como un jugador más del equipo.</p>
                </div>
            @endif

        </div>
    </div>

    <script>
        function copiarLink() {
            const input = document.getElementById('link-invitacion');
            input.select();
            navigator.clipboard.writeText(input.value);
        }
    </script>
</x-app-layout>
