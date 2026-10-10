<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Generar fixture</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">

                <p class="mb-4 text-sm text-gray-600">
                    Se creará un torneo nuevo con los <strong>{{ $totalEquipos }}</strong> equipos creados:
                    todos contra todos, ida y vuelta. Cada jornada se juega una semana después de la anterior,
                    repartida en los días que elijas.
                </p>

                @if ($errors->any())
                    <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('fixtures.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="nombre" value="Nombre del torneo" />
                        <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full"
                                      :value="old('nombre')" required autofocus />
                        <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="temporada" value="Temporada (opcional)" />
                        <x-text-input id="temporada" name="temporada" type="text" class="mt-1 block w-full"
                                      :value="old('temporada')" placeholder="2026" />
                        <x-input-error :messages="$errors->get('temporada')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="fecha_inicio" value="Fecha de inicio (primera jornada)" />
                        <x-text-input id="fecha_inicio" name="fecha_inicio" type="date" class="mt-1 block w-full"
                                      :value="old('fecha_inicio')" required />
                        <x-input-error :messages="$errors->get('fecha_inicio')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="hora_defecto" value="Hora por defecto de los partidos" />
                        <x-text-input id="hora_defecto" name="hora_defecto" type="time" class="mt-1 block w-full"
                                      :value="old('hora_defecto', '22:00')" required />
                        <x-input-error :messages="$errors->get('hora_defecto')" class="mt-2" />
                        <p class="mt-1 text-xs text-gray-400">Todos los partidos se crean con esta hora; puedes ajustarla luego partido por partido.</p>
                    </div>

                    <div>
                        <x-input-label value="Días de la semana en que se juega" />
                        @php
                            $diasLabels = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 0 => 'Dom'];
                            $diasSel = old('dias_semana', [6]);
                        @endphp
                        <div class="mt-2 flex flex-wrap gap-3">
                            @foreach ($diasLabels as $num => $label)
                                <label class="inline-flex items-center gap-1 text-sm text-gray-700">
                                    <input type="checkbox" name="dias_semana[]" value="{{ $num }}"
                                           @checked(in_array($num, (array) $diasSel))
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('dias_semana')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="partidos_por_dia" value="Partidos por día" />
                        <x-text-input id="partidos_por_dia" name="partidos_por_dia" type="number" min="1" max="50"
                                      class="mt-1 block w-full" :value="old('partidos_por_dia', 2)" required />
                        <x-input-error :messages="$errors->get('partidos_por_dia')" class="mt-2" />
                        <p class="mt-1 text-xs text-gray-400">
                            Asegúrate de que días × partidos por día alcance para los encuentros de cada jornada.
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-2">
                        <a href="{{ route('partidos.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancelar</a>
                        <x-primary-button>Generar fixture</x-primary-button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
