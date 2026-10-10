<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Convocatoria · {{ $equipo->nombre }}
            </h2>
            <a href="{{ route('convocatorias.alineacion', [$partido, $equipo]) }}"
               class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Ver alineación
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl space-y-6 sm:px-6 lg:px-8">

            <div class="rounded-md bg-white p-4 text-sm text-gray-600 shadow-sm sm:rounded-lg">
                Partido: <strong>{{ $partido->equipoLocal->nombre }}</strong> vs
                <strong>{{ $partido->equipoVisita->nombre }}</strong> ·
                Fecha {{ $partido->numero_fecha }}
                <div class="mt-1">
                    Máximo de titulares para esta modalidad: <strong>{{ $limite }}</strong>
                    (arquero incluido). El resto queda en la banca.
                </div>
                @if ($convocados->isEmpty())
                    <div class="mt-1 text-xs text-indigo-700">
                        Te dejamos una convocatoria sugerida (arquero y primeros jugadores como titulares). Ajusta lo que quieras y guarda.
                    </div>
                @endif
            </div>

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

            @if (! empty($suspendidos))
                <div class="rounded-md bg-red-50 p-3 text-xs text-red-800">
                    Hay jugadores <strong>suspendidos</strong> por tarjetas del partido anterior. No pueden ser convocados y aparecen bloqueados.
                </div>
            @endif

            @if ($arqueroFijo)
                <div class="rounded-md bg-indigo-50 p-3 text-xs text-indigo-800">
                    <strong>{{ \Illuminate\Support\Str::of($arqueroFijo->nombre)->trim()->explode(' ')->first() }}</strong>
                    está registrado como arquero, así que irá al arco automáticamente si lo convocas como titular.
                </div>
            @endif

            <form method="POST" action="{{ route('convocatorias.update', [$partido, $equipo]) }}">
                @csrf
                @method('PUT')

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3 text-sm">
                        <span class="font-medium text-gray-800">Plantel</span>
                        <span class="text-gray-500">Titulares: <strong id="cont-titulares">0</strong> / {{ $limite }}</span>
                    </div>

                    <div class="divide-y divide-gray-100">
                        @forelse ($equipo->jugadores as $j)
                            @php
                                $susp = in_array($j->id, $suspendidos);
                                $c = $convocados[$j->id] ?? null;
                                if ($c) {
                                    $estadoBase = $c->es_titular ? 'titular' : 'suplente';
                                } else {
                                    $estadoBase = $defaults[$j->id] ?? 'no';
                                }
                                $estado = old("estado.{$j->id}", $estadoBase);
                                $arqSel = (int) old('arquero_id', $arqueroConvId ?? 0) === $j->id;
                            @endphp
                            <div class="flex flex-col gap-2 px-5 py-3 text-sm sm:flex-row sm:items-center sm:justify-between {{ $susp ? 'bg-red-50/40' : '' }}">
                                <span class="font-medium text-gray-900">
                                    @if ($j->dorsal)<span class="mr-1 text-gray-400">#{{ $j->dorsal }}</span>@endif
                                    {{ $j->nombre }}
                                    @if ($j->posicion)<span class="text-gray-400">· {{ $j->posicion }}</span>@endif
                                    @if ($susp)
                                        <span class="ml-2 rounded bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">Suspendido</span>
                                    @endif
                                </span>

                                <div class="flex items-center gap-3">
                                    @if ($susp)
                                        <span class="text-xs text-gray-400">No disponible</span>
                                    @else
                                        <select name="estado[{{ $j->id }}]"
                                                class="estado-select rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="no" @selected($estado === 'no')>No convocado</option>
                                            <option value="titular" @selected($estado === 'titular')>Titular</option>
                                            <option value="suplente" @selected($estado === 'suplente')>Suplente</option>
                                        </select>

                                        @unless ($arqueroFijo)
                                            <label class="flex items-center gap-1 text-xs text-gray-600">
                                                <input type="radio" name="arquero_id" value="{{ $j->id }}"
                                                       @checked($arqSel)
                                                       class="text-indigo-600 focus:ring-indigo-500">
                                                Arquero
                                            </label>
                                        @endunless
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="px-5 py-4 text-sm text-gray-500">Este equipo no tiene jugadores cargados.</p>
                        @endforelse
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-end gap-4">
                    <a href="{{ route('partidos.show', $partido) }}" class="text-sm text-gray-600 hover:text-gray-900">Volver al partido</a>
                    <x-primary-button>Guardar convocatoria</x-primary-button>
                </div>
            </form>

        </div>
    </div>

    <script>
        (function () {
            const selects = Array.from(document.querySelectorAll('.estado-select'));
            const cont = document.getElementById('cont-titulares');
            const limite = {{ $limite }};

            function actualizar() {
                const n = selects.filter(s => s.value === 'titular').length;
                cont.textContent = n;
                cont.style.color = n > limite ? '#dc2626' : '';
            }
            selects.forEach(s => s.addEventListener('change', actualizar));
            actualizar();
        })();
    </script>
</x-app-layout>
