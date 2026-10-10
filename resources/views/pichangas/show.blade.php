<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ $pichanga->nombre }}
                    @if ($pichanga->tiene_rival)
                        <span class="font-normal text-gray-500">vs {{ $pichanga->nombreRival() }}</span>
                    @endif
                </h2>
                <p class="text-sm text-gray-500">
                    {{ $pichanga->nombreDia() }} {{ $pichanga->horaCorta() }} · {{ $pichanga->duracion_min }} min
                    @if ($pichanga->lugar) · {{ $pichanga->lugar }} @endif
                </p>
            </div>
            @if ($esOrganizador)
                <a href="{{ route('pichangas.edit', $pichanga) }}"
                   class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Editar pichanga</a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">

            @include('pichangas._alertas')

            {{-- Próxima fecha --}}
            @if ($fecha)
                @php
                    $confirmados = $fecha->asistencias->where('estado', 'confirmado')->sortBy('anotado_en');
                    $espera = $fecha->asistencias->where('estado', 'espera')->sortBy('anotado_en');
                    $bajas = $fecha->asistencias->where('estado', 'baja');
                    $porPersona = $pichanga->precioPorPersona($confirmados->count());
                    $miEstado = $miAsistencia?->estado;
                @endphp

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="text-xs font-medium uppercase tracking-wide text-gray-400">Próxima fecha</div>
                            <div class="mt-1 text-lg font-semibold capitalize text-gray-900">{{ $fecha->fechaLarga() }}</div>
                            <div class="text-sm text-gray-500">
                                {{ substr($fecha->hora, 0, 5) }} hrs ·
                                <span class="font-medium {{ $confirmados->count() >= $pichanga->cupo ? 'text-red-600' : 'text-gray-700' }}">
                                    {{ $confirmados->count() }}/{{ $pichanga->cupo }} confirmados
                                </span>
                                @if ($porPersona)
                                    · ${{ number_format($porPersona, 0, ',', '.') }} c/u
                                    <span class="text-gray-400">(total ${{ number_format($pichanga->totalAPagar(), 0, ',', '.') }}{{ $pichanga->pago_modo === 'mitad' ? ', mitad de la cancha' : '' }})</span>
                                @endif
                            </div>
                            @unless ($fecha->estaAbierta())
                                <span class="mt-2 inline-block rounded-full bg-yellow-50 px-2 py-0.5 text-xs font-medium text-yellow-700">Inscripciones cerradas</span>
                            @endunless
                        </div>

                        @if ($fecha->estaAbierta())
                            <div class="flex items-center gap-2">
                                @if ($miEstado !== 'confirmado' && $miEstado !== 'espera')
                                    <form method="POST" action="{{ route('pichangas.fechas.voy', $fecha) }}">
                                        @csrf
                                        <button type="submit" class="rounded-md bg-green-600 px-5 py-2 text-sm font-semibold text-white hover:bg-green-700">
                                            Voy
                                        </button>
                                    </form>
                                @endif
                                @if ($miEstado !== 'baja')
                                    <form method="POST" action="{{ route('pichangas.fechas.no-voy', $fecha) }}">
                                        @csrf
                                        <button type="submit" class="rounded-md border border-gray-300 bg-white px-5 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                            No voy
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if ($miEstado)
                        <p class="mt-3 text-sm">
                            @switch($miEstado)
                                @case('confirmado') <span class="font-medium text-green-700">Estás confirmado.</span> @break
                                @case('espera') <span class="font-medium text-yellow-700">Estás en lista de espera (puesto {{ $espera->values()->search(fn ($a) => $a->user_id === auth()->id()) + 1 }}).</span> @break
                                @case('baja') <span class="text-gray-500">Marcaste que no vas.</span> @break
                            @endswitch
                        </p>
                    @endif

                    <div class="mt-6 grid gap-6 sm:grid-cols-2">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700">Confirmados</h3>
                            <ol class="mt-2 space-y-1 text-sm text-gray-800">
                                @forelse ($confirmados->values() as $i => $a)
                                    <li class="flex items-center gap-2">
                                        <span class="w-5 text-right text-xs text-gray-400">{{ $i + 1 }}.</span>
                                        {{ $a->user->name }}
                                        @if ($a->equipo)
                                            <span class="rounded px-1.5 text-xs font-semibold {{ $a->equipo === 'A' ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700' }}">{{ $a->equipo }}</span>
                                        @endif
                                    </li>
                                @empty
                                    <li class="text-gray-400">Nadie todavía. ¡Sé el primero!</li>
                                @endforelse
                            </ol>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700">Lista de espera</h3>
                            <ol class="mt-2 space-y-1 text-sm text-gray-800">
                                @forelse ($espera->values() as $i => $a)
                                    <li><span class="text-xs text-gray-400">{{ $i + 1 }}.</span> {{ $a->user->name }}</li>
                                @empty
                                    <li class="text-gray-400">Vacía</li>
                                @endforelse
                            </ol>
                            @if ($bajas->isNotEmpty())
                                <h3 class="mt-4 text-sm font-semibold text-gray-700">No van</h3>
                                <p class="mt-1 text-sm text-gray-500">{{ $bajas->map(fn ($a) => $a->user->name)->join(', ') }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap items-center gap-4 border-t border-gray-100 pt-4">
                        <a href="{{ route('pichangas.fechas.show', $fecha) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                            {{ $esOrganizador ? 'Equipos, pagos y resultado →' : 'Ver equipos →' }}
                        </a>
                        @if ($esOrganizador)
                            <a href="{{ route('pichangas.fechas.show', $fecha) }}#lista-acceso" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                Lista de acceso →
                            </a>
                        @endif
                        @if ($esOrganizador)
                            <form method="POST" action="{{ route('pichangas.fechas.estado', $fecha) }}" class="ms-auto flex gap-3">
                                @csrf
                                @if ($fecha->estaAbierta())
                                    <button name="estado" value="cerrada" class="text-sm text-gray-600 hover:text-gray-900">Cerrar inscripciones</button>
                                @else
                                    <button name="estado" value="abierta" class="text-sm text-gray-600 hover:text-gray-900">Reabrir inscripciones</button>
                                @endif
                                <button name="estado" value="cancelada" class="text-sm text-red-600 hover:text-red-800"
                                        onclick="return confirm('¿Cancelar esta fecha?')">Cancelar fecha</button>
                            </form>
                        @endif
                    </div>
                </div>
            @else
                <div class="rounded-md bg-gray-50 p-4 text-sm text-gray-600">Esta pichanga está pausada.</div>
            @endif

            {{-- Link de invitación --}}
            @if ($esOrganizador && $pichanga->activa)
                <div class="bg-white p-6 shadow-sm sm:rounded-lg" x-data="{ copiado: false }">
                    <h3 class="text-sm font-semibold text-gray-700">Invitar jugadores</h3>
                    <p class="mt-1 text-sm text-gray-500">Comparte este link (por ejemplo, en el grupo de WhatsApp). Quien no tenga cuenta podrá registrarse.</p>
                    <div class="mt-3 flex gap-2">
                        <input type="text" readonly value="{{ route('pichangas.unirse', $pichanga->token) }}" x-ref="link"
                               class="block w-full rounded-md border-gray-300 bg-gray-50 text-sm shadow-sm">
                        <button type="button" class="shrink-0 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                                @click="navigator.clipboard.writeText($refs.link.value); copiado = true; setTimeout(() => copiado = false, 2000)"
                                x-text="copiado ? '¡Copiado!' : 'Copiar'">Copiar</button>
                    </div>
                </div>
            @endif

            {{-- Ranking --}}
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-sm font-semibold text-gray-700">Goleadores y asistencia</h3>
                @if ($ranking->isEmpty())
                    <p class="mt-2 text-sm text-gray-400">Aún no hay fechas jugadas.</p>
                @else
                    <table class="mt-3 w-full text-sm">
                        <thead class="text-left text-xs uppercase text-gray-400">
                            <tr><th class="py-1">Jugador</th><th class="py-1 text-right">Partidos</th><th class="py-1 text-right">Goles</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($ranking as $fila)
                                <tr class="border-t border-gray-100">
                                    <td class="py-1.5">{{ $fila->user?->name }}</td>
                                    <td class="py-1.5 text-right">{{ $fila->partidos }}</td>
                                    <td class="py-1.5 text-right font-semibold">{{ (int) $fila->goles }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Historial --}}
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-sm font-semibold text-gray-700">Fechas anteriores</h3>
                @forelse ($historial as $h)
                    <a href="{{ route('pichangas.fechas.show', $h) }}" class="mt-2 flex items-center justify-between rounded px-2 py-1.5 text-sm hover:bg-gray-50">
                        <span class="capitalize text-gray-700">{{ $h->fechaLarga() }}</span>
                        @if ($h->estado === 'cancelada')
                            <span class="text-xs text-red-500">Cancelada</span>
                        @elseif ($h->estado !== 'jugada')
                            <span class="text-xs text-yellow-600">Sin resultado</span>
                        @else
                            <span class="font-semibold text-gray-900">
                                <span class="text-blue-700">{{ $pichanga->tiene_rival ? 'Nosotros' : 'A' }}</span>
                                {{ $h->goles_a ?? '-' }} – {{ $h->goles_b ?? '-' }}
                                <span class="text-orange-700">{{ $pichanga->tiene_rival ? $pichanga->nombreRival() : 'B' }}</span>
                            </span>
                        @endif
                    </a>
                @empty
                    <p class="mt-2 text-sm text-gray-400">Todavía no hay fechas anteriores.</p>
                @endforelse
            </div>

            {{-- Miembros --}}
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-sm font-semibold text-gray-700">Miembros ({{ $miembros->count() }})</h3>
                <ul class="mt-2 divide-y divide-gray-100">
                    @foreach ($miembros as $m)
                        <li class="flex items-center justify-between py-2 text-sm">
                            <span class="text-gray-800">
                                {{ $m->name }}
                                @if ($m->pivot->rol === 'organizador')
                                    <span class="ms-1 rounded-full bg-indigo-50 px-2 py-0.5 text-xs text-indigo-700">Organizador</span>
                                @endif
                            </span>
                            @if ($m->pivot->rol !== 'organizador' && ($esOrganizador || $m->id === auth()->id()))
                                <form method="POST" action="{{ route('pichangas.miembros.destroy', [$pichanga, $m]) }}"
                                      data-confirm="{{ $m->id === auth()->id() ? '¿Salir de esta pichanga?' : '¿Quitar a '.$m->name.'?' }}"
                                      onsubmit="return confirm(this.dataset.confirm)">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600 hover:text-red-800">
                                        {{ $m->id === auth()->id() ? 'Salir' : 'Quitar' }}
                                    </button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

        </div>
    </div>
</x-app-layout>
