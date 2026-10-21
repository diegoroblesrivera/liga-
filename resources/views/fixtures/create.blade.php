<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Crear torneo</h2>
    </x-slot>

    @php
        $diasLabels = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 0 => 'Dom'];
        $diasSel = old('dias_semana', [6]);
        $total = $equipos->count();
        $opcion = 'flex cursor-pointer items-start gap-3 rounded-lg border p-3 text-sm';
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8"
             x-data="{
                etapas: {{ (int) old('etapas', 1) }},
                tipo: @js(old('tipo', 'liga')),
                grupos: {{ (int) old('grupos', max(2, intdiv($total, 4))) }},
                porGrupo: {{ (int) old('clasifican_grupo', 2) }},
                llave: {{ (int) old('llave', 8) }},
                elegidos: @js(array_map('intval', old('equipos', []))),
                total: {{ $total }},
                get clasificadosGrupos() { return this.grupos * this.porGrupo },
                get gruposValidos() { const n = this.clasificadosGrupos; return [2,4,8,16].includes(n) && Math.floor(this.total / this.grupos) > this.porGrupo },
                elegirEtapas(n) { this.etapas = n; if (n === 1 && this.tipo === 'grupos') this.tipo = 'liga'; if (n === 2 && this.tipo === 'eliminacion') this.tipo = 'liga'; },
             }">

            {{-- El torneo se arma con los equipos ya creados --}}
            @if ($total < 2)
                <div class="mb-4 rounded-lg bg-amber-50 p-5 text-sm text-amber-900 ring-1 ring-amber-200">
                    <p class="font-semibold">Primero crea los equipos</p>
                    <p class="mt-1">El torneo se arma con los equipos del campeonato y todavía {{ $total ? 'hay solo 1' : 'no hay ninguno' }}. Créalos uno a uno o cárgalos todos desde un Excel.</p>
                    <div class="mt-3 flex flex-wrap gap-3">
                        <a href="{{ route('equipos.index') }}" class="rounded-md bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700">Ir a Equipos</a>
                        <a href="{{ route('carga.equipos') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 font-medium text-gray-700 hover:bg-gray-50">📥 Cargar equipos desde Excel</a>
                    </div>
                </div>
            @else
                <div class="mb-4 rounded-lg bg-indigo-50 p-4 text-sm text-gray-700 ring-1 ring-indigo-100">
                    ℹ️ <strong>Antes de crear el torneo, todos los equipos deben estar creados en
                    <a href="{{ route('equipos.index') }}" class="font-semibold text-indigo-700 underline hover:text-indigo-900">Equipos</a>.</strong>
                    Hoy hay <strong>{{ $total }} equipos</strong>: el torneo los usa todos{{ ' ' }}<span class="text-gray-500">(en eliminación directa eliges cuáles)</span>.
                    Si falta alguno, créalo primero o <a href="{{ route('carga.equipos') }}" class="font-medium text-indigo-700 underline hover:text-indigo-900">cárgalos desde un Excel</a>.
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('fixtures.store') }}" class="space-y-5 {{ $total < 2 ? 'pointer-events-none opacity-40' : '' }}">
                @csrf
                <input type="hidden" name="etapas" :value="etapas">

                {{-- Nombre --}}
                <div class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
                    <div>
                        <x-input-label for="nombre" value="Nombre del torneo" />
                        <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" :value="old('nombre')" required autofocus />
                    </div>
                    <div>
                        <x-input-label for="temporada" value="Temporada (opcional)" />
                        <x-text-input id="temporada" name="temporada" type="text" class="mt-1 block w-full" :value="old('temporada')" placeholder="2026" />
                    </div>
                </div>

                {{-- 1. Etapas --}}
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-base font-medium text-gray-900">1. ¿En cuántas etapas quieres tu torneo?</h3>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <label class="{{ $opcion }}" :class="etapas === 1 ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'">
                            <input type="radio" class="mt-0.5 text-indigo-600" :checked="etapas === 1" @change="elegirEtapas(1)">
                            <span><strong>1 etapa</strong><span class="block text-gray-500">Todos contra todos, o eliminación directa.</span></span>
                        </label>
                        <label class="{{ $opcion }}" :class="etapas === 2 ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'">
                            <input type="radio" class="mt-0.5 text-indigo-600" :checked="etapas === 2" @change="elegirEtapas(2)">
                            <span><strong>2 etapas</strong><span class="block text-gray-500">Liga o grupos, y después eliminación directa (play offs).</span></span>
                        </label>
                    </div>
                </div>

                {{-- 2. Formato de la primera etapa --}}
                <div class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-base font-medium text-gray-900" x-text="etapas === 2 ? '2. Primera etapa' : '2. Formato'"></h3>

                    <div class="grid gap-3">
                        <label class="{{ $opcion }}" :class="tipo === 'liga' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'">
                            <input type="radio" name="tipo" value="liga" x-model="tipo" class="mt-0.5 text-indigo-600">
                            <span><strong>Torneo convencional (todos contra todos)</strong>
                                <span class="block text-gray-500">Los {{ $total }} equipos se enfrentan entre sí. <strong>Los cruces de cada fecha se sortean al azar.</strong></span></span>
                        </label>
                        <label class="{{ $opcion }}" x-show="etapas === 2" :class="tipo === 'grupos' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'">
                            <input type="radio" name="tipo" value="grupos" x-model="tipo" class="mt-0.5 text-indigo-600">
                            <span><strong>Torneo por grupos</strong>
                                <span class="block text-gray-500">Se sortean grupos que juegan todos contra todos; los mejores de cada grupo pasan a los play offs.</span></span>
                        </label>
                        <label class="{{ $opcion }}" x-show="etapas === 1" :class="tipo === 'eliminacion' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'">
                            <input type="radio" name="tipo" value="eliminacion" x-model="tipo" class="mt-0.5 text-indigo-600">
                            <span><strong>Eliminación directa (llaves)</strong>
                                <span class="block text-gray-500">8 o 16 equipos; el que pierde queda fuera. Si empatan, se define a penales.</span></span>
                        </label>
                    </div>

                    @include('fixtures._diagramas')

                    {{-- Opciones: liga y grupos --}}
                    <label class="flex items-center gap-2 text-sm text-gray-700" x-show="tipo !== 'eliminacion'">
                        <input type="checkbox" name="ida_vuelta" value="1" @checked(old('ida_vuelta')) class="rounded border-gray-300 text-indigo-600">
                        <span><strong>Partidos de ida y vuelta</strong> (primera y segunda rueda)</span>
                    </label>

                    {{-- Opciones: grupos --}}
                    <div x-show="tipo === 'grupos'" class="grid gap-4 rounded-md bg-gray-50 p-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="grupos" value="Cantidad de grupos" />
                            <select id="grupos" name="grupos" x-model.number="grupos" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                @foreach ([2, 3, 4, 6, 8] as $g)
                                    <option value="{{ $g }}">{{ $g }} grupos (~{{ $total ? round($total / $g, 1) : 0 }} equipos c/u)</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="clasifican_grupo" value="Clasifican por grupo" />
                            <select id="clasifican_grupo" name="clasifican_grupo" x-model.number="porGrupo" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="1">El 1° de cada grupo</option>
                                <option value="2">Los 2 primeros</option>
                                <option value="4">Los 4 primeros</option>
                            </select>
                        </div>
                        <p class="text-sm sm:col-span-2" :class="gruposValidos ? 'text-gray-600' : 'text-red-600'">
                            <span x-text="clasificadosGrupos"></span> equipos pasan a los play offs.
                            <span x-show="!gruposValidos">Deben ser 2, 4, 8 o 16, y cada grupo necesita más equipos que clasificados.</span>
                        </p>
                    </div>

                    {{-- Opciones: eliminación directa --}}
                    <div x-show="tipo === 'eliminacion'" class="space-y-3 rounded-md bg-gray-50 p-4">
                        <div class="flex gap-4 text-sm">
                            @foreach ([8, 16] as $n)
                                <label class="inline-flex items-center gap-2">
                                    <input type="radio" name="llave" value="{{ $n }}" x-model.number="llave" class="text-indigo-600"> {{ $n }} equipos
                                </label>
                            @endforeach
                        </div>
                        <p class="text-sm text-gray-600">Elige los equipos (<span x-text="elegidos.length"></span> de <span x-text="llave"></span>). Los cruces se sortean al azar.</p>
                        <div class="grid max-h-64 grid-cols-2 gap-1 overflow-y-auto sm:grid-cols-3">
                            @foreach ($equipos as $e)
                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" name="equipos[]" value="{{ $e->id }}" x-model.number="elegidos"
                                           :disabled="!elegidos.includes({{ $e->id }}) && elegidos.length >= llave"
                                           class="rounded border-gray-300 text-indigo-600"> {{ $e->nombre }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- 3. Segunda etapa --}}
                <div class="space-y-3 bg-white p-6 shadow-sm sm:rounded-lg" x-show="etapas === 2">
                    <h3 class="text-base font-medium text-gray-900">3. Segunda etapa: eliminación directa</h3>
                    <div x-show="tipo === 'liga'">
                        <x-input-label for="clasifican" value="¿Cuántos pasan a la eliminación directa?" />
                        <select id="clasifican" name="clasifican" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            @foreach ([2, 4, 8, 16] as $n)
                                @if ($n <= $total)
                                    <option value="{{ $n }}" @selected((int) old('clasifican', min(8, $total >= 8 ? 8 : 4)) === $n)>Los {{ $n }} primeros de la tabla</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <p class="text-sm text-gray-600">
                        Los cruces se arman por ubicación (1° vs último clasificado, 1° de un grupo vs 2° de otro) y se generan
                        cuando termina la primera etapa. Si un partido termina empatado, se define a penales.
                    </p>
                </div>

                <label class="flex items-center gap-2 bg-white p-4 text-sm text-gray-700 shadow-sm sm:rounded-lg" x-show="etapas === 2 || tipo === 'eliminacion'">
                    <input type="checkbox" name="tercer_lugar" value="1" @checked(old('tercer_lugar')) class="rounded border-gray-300 text-indigo-600">
                    <span><strong>Partido por el tercer lugar</strong> (los perdedores de las semifinales)</span>
                </label>

                {{-- Calendario --}}
                <div class="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-base font-medium text-gray-900">Calendario</h3>
                    <p class="text-sm text-gray-500">Cada fecha (o ronda) se juega una semana después de la anterior, en los días que elijas.</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="fecha_inicio" value="Fecha de inicio" />
                            <x-text-input id="fecha_inicio" name="fecha_inicio" type="date" class="mt-1 block w-full" :value="old('fecha_inicio')" required />
                        </div>
                        <div>
                            <x-input-label for="hora_defecto" value="Hora de los partidos" />
                            <x-text-input id="hora_defecto" name="hora_defecto" type="time" class="mt-1 block w-full" :value="old('hora_defecto', '22:00')" required />
                        </div>
                    </div>
                    <div>
                        <x-input-label value="Días en que se juega" />
                        <div class="mt-2 flex flex-wrap gap-3">
                            @foreach ($diasLabels as $num => $label)
                                <label class="inline-flex items-center gap-1 text-sm text-gray-700">
                                    <input type="checkbox" name="dias_semana[]" value="{{ $num }}" @checked(in_array($num, (array) $diasSel))
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"> {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <x-input-label for="partidos_por_dia" value="Partidos por día" />
                        <x-text-input id="partidos_por_dia" name="partidos_por_dia" type="number" min="1" max="50" class="mt-1 block w-full" :value="old('partidos_por_dia', 2)" required />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('partidos.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancelar</a>
                    <x-primary-button>Crear torneo</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
