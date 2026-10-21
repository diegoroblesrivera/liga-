<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ $partido->equipoLocal->nombre }} vs {{ $partido->equipoVisita->nombre }}
            </h2>

            <div class="flex items-center gap-3">
                @can('update', $partido)
                    <a href="{{ route('partidos.edit', $partido) }}"
                       class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Reprogramar
                    </a>
                @endcan
                @can('delete', $partido)
                    @php
                        $perdidas = collect([
                            $partido->estaFinalizado() ? 'su resultado (cambia la tabla de posiciones)' : null,
                            $partido->goles->isNotEmpty() ? $partido->goles->count().' goles (cambian los goleadores)' : null,
                            $partido->pagos->isNotEmpty() ? $partido->pagos->count().' pagos con sus comprobantes' : null,
                        ])->filter();
                        $aviso = '¿Eliminar este partido?'
                            .($perdidas->isNotEmpty() ? ' Se borrarán también '.$perdidas->join(', ', ' y ').'.' : '')
                            .' No se puede deshacer.';
                    @endphp
                    <form method="POST" action="{{ route('partidos.destroy', $partido) }}"
                          data-confirm="{{ $aviso }}" onsubmit="return confirm(this.dataset.confirm)">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-md border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">
                            Eliminar
                        </button>
                    </form>
                @endcan
            </div>
        </div>
    </x-slot>

    @php
        $yo = auth()->user();
        $golesLocal = $partido->goles->where('equipo_id', $partido->equipo_local_id)->count();
        $golesVisita = $partido->goles->where('equipo_id', $partido->equipo_visita_id)->count();

        $esAdminPago = \Illuminate\Support\Facades\Gate::allows('gestionar-pagos');
        $esDtDelPartido = $yo && $yo->equipo_id && in_array($yo->equipo_id, [
            $partido->equipo_local_id, $partido->equipo_visita_id,
        ]);

        $alLocal = \App\Support\Formacion::delPartido($partido, $partido->equipoLocal);
        $alVisita = \App\Support\Formacion::delPartido($partido, $partido->equipoVisita);

        // Tarjetas de este partido, agrupadas por jugador para detectar expulsiones.
        $tarjetas = \App\Models\Tarjeta::where('partido_id', $partido->id)->with('jugador')->get();
        $tarjPorJugador = $tarjetas->groupBy('jugador_id');

        // Suspendidos para ESTE partido (por el partido anterior de cada equipo).
        $suspLocal = \App\Support\Suspension::suspendidosEnPartido($partido, $partido->equipo_local_id);
        $suspVisita = \App\Support\Suspension::suspendidosEnPartido($partido, $partido->equipo_visita_id);
        $suspTodos = array_merge($suspLocal, $suspVisita);

        // Lista de asistencia (si el campeonato la pide)
        $ligaLista = \App\Support\LigaActual::liga();
        $conLista = (bool) $ligaLista?->pedir_lista;
        $asistencias = $conLista ? $partido->asistencias()->get()->keyBy('jugador_id') : collect();
        $exigeLista = $conLista && $ligaLista->exigeLista();
        $gestionaLista = $conLista && $yo && \App\Support\Lista::puedeMarcar($yo, $partido);
        $miFicha = $yo ? $partido->equipoLocal->jugadores->merge($partido->equipoVisita->jugadores)->firstWhere('user_id', $yo->id) : null;
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">

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

            {{-- Suspendidos para este partido --}}
            @if (! empty($suspTodos))
                @php
                    $nombresSusp = $partido->equipoLocal->jugadores
                        ->merge($partido->equipoVisita->jugadores)
                        ->whereIn('id', $suspTodos);
                @endphp
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800 shadow-sm sm:rounded-lg">
                    <div class="font-medium">No disponibles por suspensión</div>
                    <div class="mt-1">
                        {{ $nombresSusp->pluck('nombre')->implode(', ') }}
                        <span class="text-red-600">— inhabilitados por tarjetas del partido anterior.</span>
                    </div>
                </div>
            @endif

            {{-- Póster del partido (oculto por defecto) --}}
            <div class="bg-white p-5 shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-medium text-gray-900">Póster del partido</h3>
                    <div class="flex items-center gap-2">
                        <button type="button" id="btn-toggle-poster" onclick="togglePoster()"
                                class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">
                            Mostrar póster
                        </button>
                        <button type="button" id="btn-descargar-poster" onclick="descargarPoster(this)"
                                class="hidden rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                            Descargar imagen
                        </button>
                    </div>
                </div>

                <div id="poster-container" class="mt-4 hidden flex justify-center overflow-x-auto">
                    @include('partidos._poster', ['partido' => $partido])
                </div>
            </div>

            {{-- Alineación del partido (oculta por defecto) --}}
            <div class="bg-white p-5 shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-medium text-gray-900">Alineación del partido</h3>
                    <div class="flex items-center gap-2">
                        <button type="button" id="btn-toggle-vs" onclick="toggleVS()"
                                class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">
                            Mostrar alineación del partido
                        </button>
                        <button type="button" id="btn-descargar-vs" onclick="descargarVS(this)"
                                class="hidden rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                            Descargar imagen
                        </button>
                    </div>
                </div>

                <div id="vs-container" class="mt-4 hidden">
                    @include('partidos._cancha_vs', [
                        'local' => $partido->equipoLocal,
                        'visita' => $partido->equipoVisita,
                        'alLocal' => $alLocal,
                        'alVisita' => $alVisita,
                    ])
                </div>
            </div>

            {{-- Accesos a convocatoria --}}
            @if ($esAdminPago || $esDtDelPartido)
                <div class="flex flex-wrap gap-3 rounded-md bg-white p-4 text-sm shadow-sm sm:rounded-lg">
                    @if ($yo?->hasRole('admin'))
                        <a href="{{ route('convocatorias.edit', [$partido, $partido->equipo_local_id]) }}"
                           class="font-medium text-indigo-600 hover:text-indigo-800">Convocatoria {{ $partido->equipoLocal->nombre }}</a>
                        <a href="{{ route('convocatorias.edit', [$partido, $partido->equipo_visita_id]) }}"
                           class="font-medium text-indigo-600 hover:text-indigo-800">Convocatoria {{ $partido->equipoVisita->nombre }}</a>
                    @elseif ($esDtDelPartido)
                        <a href="{{ route('convocatorias.edit', [$partido, $yo->equipo_id]) }}"
                           class="font-medium text-indigo-600 hover:text-indigo-800">Convocar mi equipo</a>
                    @endif
                </div>
            @endif

            {{-- Marcador --}}
            <div class="bg-white p-6 text-center shadow-sm sm:rounded-lg">
                <div class="text-sm text-gray-500">{{ $partido->etiqueta() }} · {{ $partido->torneo->nombre }}</div>
                <div class="mt-4 flex items-center justify-center gap-6">
                    <span class="text-lg font-medium text-gray-900">{{ $partido->equipoLocal->nombre }}</span>
                    @if ($partido->estaProgramado())
                        <span class="text-2xl text-gray-400">vs</span>
                    @else
                        <span class="marcador text-6xl text-balon">{{ $golesLocal }} - {{ $golesVisita }}</span>
                    @endif
                    <span class="text-lg font-medium text-gray-900">{{ $partido->equipoVisita->nombre }}</span>
                </div>
                @if ($partido->penales_local !== null)
                    <div class="mt-1 text-sm font-medium text-gray-700">Penales: {{ $partido->penales_local }} - {{ $partido->penales_visita }}</div>
                @endif
                <div class="mt-3 text-sm font-medium text-gray-600">
                    {{ ucfirst(str_replace('_', ' ', $partido->estado)) }}
                </div>

                <div class="mt-4 flex justify-center gap-3">
                    @can('iniciar', $partido)
                        <form method="POST" action="{{ route('partidos.iniciar', $partido) }}">
                            @csrf
                            <x-primary-button>Iniciar partido</x-primary-button>
                        </form>
                    @endcan

                    @can('finalizar', $partido)
                        <form method="POST" action="{{ route('partidos.finalizar', $partido) }}"
                              onsubmit="return confirm('Al finalizar, el resultado se congela y no podrás editar los goles. ¿Continuar?')">
                            @csrf
                            @if ($partido->esEliminacion() && $golesLocal === $golesVisita)
                                <div class="mb-3 rounded-md bg-amber-50 p-3 text-left text-sm text-amber-900">
                                    <p class="font-medium">Empate en eliminación directa: anota los penales.</p>
                                    <div class="mt-2 flex items-center gap-2">
                                        <span class="text-xs">{{ $partido->equipoLocal->nombre }}</span>
                                        <input type="number" name="penales_local" min="0" max="50" required value="{{ old('penales_local') }}" class="w-16 rounded-md border-gray-300 py-1 text-center text-sm">
                                        <span>-</span>
                                        <input type="number" name="penales_visita" min="0" max="50" required value="{{ old('penales_visita') }}" class="w-16 rounded-md border-gray-300 py-1 text-center text-sm">
                                        <span class="text-xs">{{ $partido->equipoVisita->nombre }}</span>
                                    </div>
                                </div>
                            @endif
                            <x-primary-button>Finalizar partido</x-primary-button>
                        </form>
                    @endcan
                </div>
            </div>

            {{-- Lista de asistencia --}}
            @if ($conLista && ($gestionaLista || $miFicha))
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-base font-medium text-gray-900">
                            📋 Lista de asistencia
                            @if ($exigeLista)<span class="ms-1 rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700">sin lista no se juega</span>@endif
                        </h3>
                        @if (\App\Support\Lista::puedeGestionarFecha($yo, collect([$partido])))
                            <a href="{{ route('lista.fecha', [$partido->torneo_id, $partido->numero_fecha]) }}"
                               class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Mostrar QR de la fecha →</a>
                        @endif
                    </div>
                    @if ($gestionaLista)
                        <div class="grid gap-4 sm:grid-cols-2">
                            @include('lista._equipo', ['equipo' => $partido->equipoLocal])
                            @include('lista._equipo', ['equipo' => $partido->equipoVisita])
                        </div>
                    @elseif ($asistencias->has($miFicha->id))
                        <p class="text-sm text-green-700">✅ Estás en la lista desde las {{ $asistencias[$miFicha->id]->created_at->format('H:i') }}.</p>
                    @else
                        <p class="text-sm text-gray-600">Aún no estás en la lista. En la cancha, escanea el QR que muestra el árbitro, el DT o la organización.</p>
                    @endif
                </div>
            @endif

            {{-- Registrar gol --}}
            @can('create', [App\Models\Gol::class, $partido])
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="mb-4 text-base font-medium text-gray-900">Registrar gol</h3>
                    <form method="POST" action="{{ route('goles.store', $partido) }}"
                          class="grid grid-cols-1 gap-4 sm:grid-cols-4 sm:items-end">
                        @csrf
                        <div class="sm:col-span-2">
                            <x-input-label for="jugador_id" value="Jugador" />
                            <select id="jugador_id" name="jugador_id" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <optgroup label="{{ $partido->equipoLocal->nombre }}">
                                    @foreach ($partido->equipoLocal->jugadores as $j)
                                        <option value="{{ $j->id }}" @disabled($exigeLista && ! $asistencias->has($j->id))>{{ $j->dorsal ? '#' . $j->dorsal . ' - ' : '' }}{{ $j->nombre }}{{ $exigeLista && ! $asistencias->has($j->id) ? ' (sin lista)' : '' }}</option>
                                         
                                    @endforeach
                                </optgroup>
                                <optgroup label="{{ $partido->equipoVisita->nombre }}">
                                    @foreach ($partido->equipoVisita->jugadores as $j)
                                        <option value="{{ $j->id }}" @disabled($exigeLista && ! $asistencias->has($j->id))>{{ $j->dorsal ? '#' . $j->dorsal . ' - ' : '' }}{{ $j->nombre }}{{ $exigeLista && ! $asistencias->has($j->id) ? ' (sin lista)' : '' }}</option>
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="tipo" value="Tipo" />
                            <select id="tipo" name="tipo"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="normal">Normal</option>
                                <option value="penal">Penal</option>
                                <option value="autogol">Autogol</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="minuto" value="Minuto" />
                            <x-text-input id="minuto" name="minuto" type="number" min="0" max="200" class="mt-1 block w-full" />
                        </div>
                        <div class="flex justify-end sm:col-span-4">
                            <x-primary-button>Agregar gol</x-primary-button>
                        </div>
                    </form>
                    <p class="mt-2 text-xs text-gray-400">El autogol se acredita automáticamente al equipo rival.</p>
                </div>
            @endcan

            {{-- Registrar tarjeta --}}
            @can('create', [App\Models\Tarjeta::class, $partido])
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="mb-4 text-base font-medium text-gray-900">Registrar tarjeta</h3>
                    <form method="POST" action="{{ route('tarjetas.store', $partido) }}"
                          class="grid grid-cols-1 gap-4 sm:grid-cols-4 sm:items-end">
                        @csrf
                        <div class="sm:col-span-2">
                            <x-input-label for="tarjeta_jugador_id" value="Jugador" />
                            <select id="tarjeta_jugador_id" name="jugador_id" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <optgroup label="{{ $partido->equipoLocal->nombre }}">
                                    @foreach ($partido->equipoLocal->jugadores as $j)
                                        <option value="{{ $j->id }}" @disabled($exigeLista && ! $asistencias->has($j->id))>{{ $j->dorsal ? '#' . $j->dorsal . ' - ' : '' }}{{ $j->nombre }}{{ $exigeLista && ! $asistencias->has($j->id) ? ' (sin lista)' : '' }}</option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="{{ $partido->equipoVisita->nombre }}">
                                    @foreach ($partido->equipoVisita->jugadores as $j)
                                        <option value="{{ $j->id }}" @disabled($exigeLista && ! $asistencias->has($j->id))>{{ $j->dorsal ? '#' . $j->dorsal . ' - ' : '' }}{{ $j->nombre }}{{ $exigeLista && ! $asistencias->has($j->id) ? ' (sin lista)' : '' }}</option>
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="tarjeta_tipo" value="Tipo" />
                            <select id="tarjeta_tipo" name="tipo"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="amarilla">Amarilla</option>
                                <option value="roja">Roja</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="tarjeta_minuto" value="Minuto" />
                            <x-text-input id="tarjeta_minuto" name="minuto" type="number" min="0" max="200" class="mt-1 block w-full" />
                        </div>
                        <div class="sm:col-span-4">
                            <x-input-label for="tarjeta_comentario" value="Comentario (opcional)" />
                            <x-text-input id="tarjeta_comentario" name="comentario" type="text" maxlength="200" class="mt-1 block w-full" />
                        </div>
                        <div class="flex justify-end sm:col-span-4">
                            <x-primary-button>Agregar tarjeta</x-primary-button>
                        </div>
                    </form>
                    <p class="mt-2 text-xs text-gray-400">Dos amarillas a un mismo jugador equivalen a roja (expulsión).</p>
                </div>
            @endcan

            {{-- Datos (resumen) --}}
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-gray-500">Fecha y hora</dt>
                        <dd class="text-gray-900">{{ $partido->fecha_hora?->format('d/m/Y H:i') ?? 'Sin definir' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Cancha</dt>
                        <dd class="text-gray-900">{{ $partido->cancha?->nombre ?? 'Sin definir' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Árbitro</dt>
                        <dd class="text-gray-900">{{ $partido->arbitro?->name ?? 'Sin asignar' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Jornada</dt>
                        <dd class="text-gray-900">{{ $partido->etiqueta() }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Pago del partido --}}
            @if ($esAdminPago || ($esDtDelPartido && $partido->monto_pago !== null))
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="mb-4 text-base font-medium text-gray-900">Pago del partido</h3>

                    @if ($esAdminPago)
                        @if ($partido->monto_pago !== null)
                            <p class="mb-3 text-sm text-gray-600">
                                Monto asignado: <strong>${{ number_format($partido->monto_pago, 0, ',', '.') }}</strong> por equipo
                            </p>
                            <ul class="mb-3 space-y-2">
                                @foreach ([$partido->equipoLocal, $partido->equipoVisita] as $equipo)
                                    @php($pago = $partido->pagos->firstWhere('equipo_id', $equipo->id))
                                    <li class="flex items-center justify-between rounded border p-3 text-sm">
                                        <span>{{ $equipo->nombre }}</span>
                                        @if ($pago && $pago->comprobante_path)
                                            <a href="{{ route('pagos.comprobante.ver', $pago) }}" target="_blank"
                                               class="rounded bg-green-100 px-2 py-1 text-xs text-green-800">Ver comprobante</a>
                                        @else
                                            <span class="rounded bg-yellow-100 px-2 py-1 text-xs text-yellow-800">Pendiente</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="mb-3 text-sm text-gray-500">Aún no se ha asignado un monto de pago para este partido.</p>
                        @endif
                        <a href="{{ route('partidos.pago.edit', $partido) }}"
                           class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                            {{ $partido->monto_pago !== null ? 'Editar monto' : 'Asignar monto de pago' }}
                        </a>
                    @else
                        @php($miPago = $partido->pagos->firstWhere('equipo_id', $yo->equipo_id))
                        <p class="mb-3 text-sm text-gray-600">
                            Monto a pagar: <strong>${{ number_format($partido->monto_pago, 0, ',', '.') }}</strong>
                        </p>
                        @if ($miPago && $miPago->comprobante_path)
                            <span class="rounded-full bg-green-100 px-3 py-1 text-xs text-green-800">Comprobante subido</span>
                            <a href="{{ route('pagos.comprobante.create', $partido) }}" class="ml-2 text-sm text-indigo-600 underline">Reemplazar</a>
                        @else
                            <a href="{{ route('pagos.comprobante.create', $partido) }}"
                               class="inline-block rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700">
                                Subir comprobante
                            </a>
                        @endif
                    @endif
                </div>
            @endif

            {{-- Edición rápida de fecha, hora y cancha (solo admin) --}}
            @can('update', $partido)
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="mb-4 text-base font-medium text-gray-900">Fecha, hora y cancha</h3>
                    <form method="POST" action="{{ route('partidos.actualizarProgramacion', $partido) }}"
                          class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:items-end">
                        @csrf
                        <div>
                            <x-input-label for="fecha_hora" value="Fecha y hora" />
                            <x-text-input id="fecha_hora" name="fecha_hora" type="datetime-local" class="mt-1 block w-full"
                                          :value="optional($partido->fecha_hora)->format('Y-m-d\TH:i')" />
                        </div>
                        <div>
                            <x-input-label for="cancha_id" value="Cancha" />
                            <select id="cancha_id" name="cancha_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Sin definir</option>
                                @foreach ($canchas as $c)
                                    <option value="{{ $c->id }}" @selected($partido->cancha_id === $c->id)>{{ $c->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex justify-end">
                            <x-primary-button>Guardar</x-primary-button>
                        </div>
                    </form>
                </div>
            @endcan

            {{-- Asignar árbitro (solo admin) --}}
            @can('update', $partido)
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="mb-4 text-base font-medium text-gray-900">Asignar árbitro</h3>
                    <form method="POST" action="{{ route('partidos.asignarArbitro', $partido) }}"
                          class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        @csrf
                        <div class="flex-1">
                            <x-input-label for="arbitro_id" value="Árbitro" />
                            <select id="arbitro_id" name="arbitro_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Sin asignar</option>
                                @foreach ($arbitros as $a)
                                    <option value="{{ $a->id }}" @selected($partido->arbitro_id === $a->id)>{{ $a->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-primary-button>Guardar</x-primary-button>
                    </form>
                    @if ($arbitros->isEmpty())
                        <p class="mt-2 text-xs text-gray-400">
                            No hay árbitros registrados todavía. Genera un link en la sección Árbitros.
                        </p>
                    @endif
                </div>
            @endcan

            {{-- Goles (gol por gol) --}}
            @if ($partido->goles->isNotEmpty())
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="mb-4 text-base font-medium text-gray-900">Goles</h3>
                    @foreach ($partido->goles->sortBy('minuto') as $gol)
                        <div class="flex items-center justify-between border-b border-gray-100 py-2 text-sm last:border-0">
                            <span class="text-gray-900">
                                {{ $gol->jugador->nombre }}
                                @if ($gol->tipo !== 'normal')
                                    <span class="text-gray-400">({{ $gol->tipo }})</span>
                                @endif
                            </span>
                            <div class="flex items-center gap-3">
                                <span class="text-gray-500">{{ $gol->minuto !== null ? $gol->minuto."'" : '' }}</span>
                                @can('delete', $gol)
                                    <form method="POST" action="{{ route('goles.destroy', $gol) }}"
                                          onsubmit="return confirm('¿Eliminar este gol?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600 hover:text-red-800">Eliminar</button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

{{-- Tarjetas --}}
            @if ($tarjetas->isNotEmpty())
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="mb-4 text-base font-medium text-gray-900">Tarjetas</h3>
                    @foreach ($tarjetas->sortBy('minuto') as $t)
                        <div class="flex items-center justify-between border-b border-gray-100 py-2 text-sm last:border-0">
                            <span class="flex items-center gap-2 text-gray-900">
                               <span>{{ $t->tipo === 'roja' ? '🟥' : '🟨' }}</span>
                                {{ $t->jugador->nombre }}
                                @if ($t->tipo === 'amarilla' && $tarjPorJugador[$t->jugador_id]->where('tipo', 'amarilla')->count() >= 2)
                                    <span class="rounded bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">Doble amarilla (roja)</span>
                                @endif
                                @if ($t->comentario)
                                    <span class="text-gray-400">{{ $t->comentario }}</span>
                                @endif
                            </span>
                            <div class="flex items-center gap-3">
                                <span class="text-gray-500">@if ($t->minuto !== null){{ $t->minuto }}'@endif</span>
                                @can('delete', $t)
                                    <form method="POST" action="{{ route('tarjetas.destroy', $t) }}"
                                          onsubmit="return confirm('Eliminar esta tarjeta?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600 hover:text-red-800">Eliminar</button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        function togglePoster() {
            const c = document.getElementById('poster-container');
            const b = document.getElementById('btn-toggle-poster');
            const d = document.getElementById('btn-descargar-poster');
            const oculto = c.classList.toggle('hidden');
            b.textContent = oculto ? 'Mostrar póster' : 'Ocultar póster';
            d.classList.toggle('hidden', oculto);
        }

        function descargarPoster(btn) {
            const poster = document.getElementById('poster-canvas');
            if (!poster || typeof html2canvas === 'undefined') return;
            if (btn) { btn.disabled = true; btn.textContent = 'Generando…'; }

            html2canvas(poster, {
                useCORS: true, backgroundColor: null, scale: 2,
                width: 480, height: 720
            }).then(function (canvas) {
                const a = document.createElement('a');
                a.href = canvas.toDataURL('image/png');
                a.download = 'poster-partido-{{ $partido->id }}.png';
                a.click();
            }).finally(function () {
                if (btn) { btn.disabled = false; btn.textContent = 'Descargar imagen'; }
            });
        }

        function toggleVS() {
            const c = document.getElementById('vs-container');
            const b = document.getElementById('btn-toggle-vs');
            const d = document.getElementById('btn-descargar-vs');
            const oculto = c.classList.toggle('hidden');
            b.textContent = oculto ? 'Mostrar alineación del partido' : 'Ocultar alineación del partido';
            d.classList.toggle('hidden', oculto);
        }

        function descargarVS(btn) {
            const cancha = document.getElementById('cancha-vs');
            if (!cancha || typeof html2canvas === 'undefined') return;
            if (btn) { btn.disabled = true; btn.textContent = 'Generando…'; }

            const rect = cancha.getBoundingClientRect();
            const w = Math.ceil(rect.width) + 2;
            const h = Math.ceil(cancha.scrollHeight) + 2;

            html2canvas(cancha, {
                useCORS: true, backgroundColor: null, scale: 2,
                width: w, height: h,
                windowWidth: document.documentElement.scrollWidth,
                scrollX: 0, scrollY: -window.scrollY
            }).then(function (canvas) {
                const a = document.createElement('a');
                a.href = canvas.toDataURL('image/png');
                a.download = 'alineacion-partido-{{ $partido->id }}.png';
                a.click();
            }).finally(function () {
                if (btn) { btn.disabled = false; btn.textContent = 'Descargar imagen'; }
            });
        }
    </script>
</x-app-layout>
