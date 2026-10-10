<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('pichangas.show', $pichanga) }}" class="text-sm text-gray-500 hover:text-gray-700">← {{ $pichanga->nombre }}</a>
            <h2 class="text-xl font-semibold capitalize leading-tight text-gray-800">{{ $fecha->fechaLarga() }}</h2>
        </div>
    </x-slot>

    @php
        $confirmados = $fecha->asistencias->where('estado', 'confirmado')->sortBy('anotado_en')->values();
        $equipoA = $confirmados->where('equipo', 'A');
        $equipoB = $confirmados->where('equipo', 'B');
        $porPersona = $pichanga->precioPorPersona($confirmados->count());
        // Con rival juegan todos juntos contra otro grupo: no hay equipos A/B.
        $rival = $pichanga->tiene_rival;
        $ladoA = $rival ? 'Nosotros' : 'Equipo A';
        $ladoB = $rival ? $pichanga->nombreRival() : 'Equipo B';
        $miAsistencia = $fecha->asistencias->firstWhere('user_id', auth()->id());
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">

            @include('pichangas._alertas')

            {{-- Mi pago (cualquier confirmado, también en fechas ya jugadas) --}}
            @include('pichangas._mi-pago')

            {{-- Marcador y equipos --}}
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-center gap-6 text-center">
                    <div class="w-28 text-sm font-semibold text-blue-700">{{ $ladoA }}</div>
                    <div class="marcador text-6xl text-balon">{{ $fecha->goles_a ?? '-' }} – {{ $fecha->goles_b ?? '-' }}</div>
                    <div class="w-28 text-sm font-semibold text-orange-700">{{ $ladoB }}</div>
                </div>
                <p class="mt-1 text-center text-xs uppercase tracking-wide text-gray-400">{{ $fecha->estado }}</p>

                @if ($rival)
                    <ul class="mx-auto mt-6 max-w-xs space-y-1 text-sm">
                        @forelse ($confirmados as $a)
                            <li class="flex justify-between">
                                <span>{{ $a->nombreVisible() }}</span>
                                @if ($a->goles) <span class="text-gray-500">⚽ {{ $a->goles }}</span> @endif
                            </li>
                        @empty
                            <li class="text-center text-gray-400">Sin confirmados</li>
                        @endforelse
                    </ul>
                @else
                    <div class="mt-6 grid grid-cols-2 gap-6 text-sm">
                        @foreach (['A' => $equipoA, 'B' => $equipoB] as $letra => $equipo)
                            <ul class="space-y-1">
                                @forelse ($equipo as $a)
                                    <li class="flex justify-between {{ $letra === 'B' ? 'flex-row-reverse text-right' : '' }}">
                                        <span>{{ $a->nombreVisible() }}</span>
                                        @if ($a->goles) <span class="text-gray-500">⚽ {{ $a->goles }}</span> @endif
                                    </li>
                                @empty
                                    <li class="text-gray-400">Sin jugadores asignados</li>
                                @endforelse
                            </ul>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Lista de acceso (solo organizador: contiene RUN) --}}
            @if ($esOrganizador)
                @php
                    $sinRun = $confirmados->filter(fn ($a) => ! $a->run());
                    $totalAcompanantes = $confirmados->sum(fn ($a) => $a->user?->acompanantes->count() ?? 0);
                @endphp
                <div id="lista-acceso" class="bg-white p-6 shadow-sm sm:rounded-lg" x-data="{ copiado: false }">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700">Lista de acceso</h3>
                            <p class="text-xs text-gray-500">{{ $confirmados->count() }} {{ $confirmados->count() === 1 ? 'jugador' : 'jugadores' }} + {{ $totalAcompanantes }} {{ $totalAcompanantes === 1 ? 'acompañante' : 'acompañantes' }}</p>
                        </div>
                        <button type="button" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                                @click="navigator.clipboard.writeText($refs.lista.innerText); copiado = true; setTimeout(() => copiado = false, 2000)"
                                x-text="copiado ? '¡Copiada!' : 'Copiar lista'">Copiar lista</button>
                    </div>

                    @if ($sinRun->isNotEmpty())
                        <p class="mt-3 rounded-md bg-yellow-50 p-3 text-sm text-yellow-800">
                            Sin RUN registrado: {{ $sinRun->map(fn ($a) => $a->nombreVisible())->join(', ') }}.
                            Pídeles que lo completen en su perfil.
                        </p>
                    @endif

                    @if ($confirmados->isEmpty())
                        <p class="mt-3 text-sm text-gray-400">No hay confirmados en esta fecha.</p>
                    @else
                        <pre x-ref="lista" class="mt-3 max-h-96 overflow-auto whitespace-pre-wrap rounded-md bg-gray-50 p-4 font-sans text-sm text-gray-800">{{ $fecha->listaAcceso() }}</pre>
                    @endif
                </div>
            @endif

            {{-- Planilla del organizador --}}
            @if ($esOrganizador)
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700">Planilla</h3>
                        @unless ($rival)
                            <form method="POST" action="{{ route('pichangas.fechas.sortear', $fecha) }}"
                                  onsubmit="return confirm('Esto reemplaza los equipos actuales. ¿Sortear?')">
                                @csrf
                                <button type="submit" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50">
                                    🎲 Sortear equipos
                                </button>
                            </form>
                        @endunless
                    </div>

                    @if ($porPersona)
                        <p class="mt-1 text-sm text-gray-500">
                            Total ${{ number_format($pichanga->totalAPagar(), 0, ',', '.') }}{{ $pichanga->pago_modo === 'mitad' ? ' (mitad de la cancha)' : '' }}
                            ÷ {{ $confirmados->count() }} = <strong>${{ number_format($porPersona, 0, ',', '.') }} por persona</strong> ·
                            pagaron {{ $confirmados->where('pagado', true)->count() }} de {{ $confirmados->count() }}
                        </p>
                    @endif

                    <form method="POST" action="{{ route('pichangas.fechas.planilla', $fecha) }}" class="mt-4">
                        @csrf
                        @method('PUT')

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="text-left text-xs uppercase text-gray-400">
                                    <tr>
                                        <th class="py-2">Jugador</th>
                                        @unless ($rival)<th class="py-2">Equipo</th>@endunless
                                        <th class="py-2 text-center">Pagó</th>
                                        <th class="py-2 text-center">Comprobante</th>
                                        <th class="py-2 text-right">Goles</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($confirmados as $a)
                                        <tr class="border-t border-gray-100">
                                            <td class="py-2 pe-2">{{ $a->nombreVisible() }}@if ($a->pagadoPor) <span class="block text-xs text-gray-400">pagó {{ $a->pagadoPor->name }}</span>@endif</td>
                                            @unless ($rival)
                                                <td class="py-2 pe-2">
                                                    <select name="jugadores[{{ $a->id }}][equipo]"
                                                            class="rounded-md border-gray-300 py-1 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                        <option value="">—</option>
                                                        <option value="A" @selected($a->equipo === 'A')>A</option>
                                                        <option value="B" @selected($a->equipo === 'B')>B</option>
                                                    </select>
                                                </td>
                                            @endunless
                                            <td class="py-2 text-center">
                                                <input type="checkbox" name="jugadores[{{ $a->id }}][pagado]" value="1" @checked($a->pagado)
                                                       title="Marca a mano los pagos presenciales"
                                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                            </td>
                                            <td class="py-2 text-center">
                                                @if ($a->comprobante_path)
                                                    <a href="{{ route('pichangas.comprobante.show', $a) }}" target="_blank"
                                                       title="Subido el {{ $a->comprobante_subido_en->format('d/m H:i') }}"
                                                       class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Ver</a>
                                                @else
                                                    <span class="text-xs text-gray-300">—</span>
                                                @endif
                                            </td>
                                            <td class="py-2 text-right">
                                                <input type="number" min="0" max="50" name="jugadores[{{ $a->id }}][goles]" value="{{ $a->goles }}"
                                                       class="w-16 rounded-md border-gray-300 py-1 text-right text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="py-4 text-center text-gray-400">No hay confirmados en esta fecha.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6 flex flex-wrap items-end gap-4 border-t border-gray-100 pt-4">
                            <div>
                                <x-input-label for="goles_a" :value="'Goles '.$ladoA" />
                                <x-text-input id="goles_a" name="goles_a" type="number" min="0" max="99" class="mt-1 w-20" :value="old('goles_a', $fecha->goles_a)" />
                            </div>
                            <div>
                                <x-input-label for="goles_b" :value="'Goles '.$ladoB" />
                                <x-text-input id="goles_b" name="goles_b" type="number" min="0" max="99" class="mt-1 w-20" :value="old('goles_b', $fecha->goles_b)" />
                            </div>
                            @if ($fecha->estado !== 'jugada')
                                <label class="flex items-center gap-2 pb-2 text-sm text-gray-700">
                                    <input type="checkbox" name="jugada" value="1"
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    Marcar como jugada
                                </label>
                            @endif
                            <x-primary-button class="ms-auto">Guardar planilla</x-primary-button>
                        </div>
                        @if ($fecha->estado !== 'jugada')
                            <p class="mt-2 text-xs text-gray-500">Al marcarla como jugada, la próxima vez que se abra la pichanga se crea la fecha de la semana siguiente.</p>
                        @endif
                    </form>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
