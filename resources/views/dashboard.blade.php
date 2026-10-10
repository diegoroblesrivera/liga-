<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Inicio</h2>
    </x-slot>

    @php
        $user = auth()->user();
        $equipoDt = $user?->equipo;
        $participa = $user->participaEnTorneos();
        $miEquipo = $equipoDt ?? $user->jugador?->equipo;
        $misPichangas = $user->pichangas()->where('activa', true)->orderBy('dia_semana')->get();
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">

            @if (session('status') && is_string(session('status')) && session('status') !== 'profile-updated')
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>
            @endif

            {{-- Las dos secciones de la app --}}
            <div class="grid gap-6 sm:grid-cols-2">

                {{-- Mis torneos --}}
                <div class="flex flex-col bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="text-3xl">🏆</div>
                    <h3 class="mt-2 text-lg font-semibold text-gray-900">Mis torneos</h3>

                    @if ($participa)
                        <p class="mt-1 flex-1 text-sm text-gray-600">
                            <span class="font-medium text-gray-800">{{ \App\Support\LigaActual::liga()?->nombre }}</span>.
                            @if ($miEquipo)
                                Tu equipo: <span class="font-medium text-gray-800">{{ $miEquipo->nombre }}</span>.
                            @endif
                            Posiciones, partidos, goleadores y pagos del campeonato.
                            @if (count($user->ligasIds()) > 1)
                                <span class="text-gray-500">Cambia de campeonato desde el menú 🏆.</span>
                            @endif
                        </p>
                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <a href="{{ route('tablas.posiciones') }}"
                               class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                Ver mis torneos
                            </a>
                            <a href="{{ route('partidos.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Partidos</a>
                            @if ($miEquipo)
                                <a href="{{ route('equipos.show', $miEquipo) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Mi equipo</a>
                            @endif
                        </div>
                    @else
                        <p class="mt-1 flex-1 text-sm text-gray-500">
                            No participas en ningún torneo. Crea el tuyo, o si juegas en un equipo,
                            pídele a tu DT el link de inscripción.
                        </p>
                        <div class="mt-4">
                            <a href="{{ route('campeonatos.create') }}"
                               class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                Crear campeonato
                            </a>
                        </div>
                    @endif
                </div>

                {{-- Mis pichangas --}}
                <div class="flex flex-col bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="text-3xl">⚽</div>
                    <h3 class="mt-2 text-lg font-semibold text-gray-900">Mis pichangas</h3>

                    @if ($misPichangas->isNotEmpty())
                        <ul class="mt-2 flex-1 space-y-1 text-sm text-gray-600">
                            @foreach ($misPichangas->take(3) as $p)
                                <li>
                                    <a href="{{ route('pichangas.show', $p) }}" class="font-medium text-gray-800 hover:text-indigo-700">{{ $p->nombre }}</a>
                                    <span class="text-gray-500">· {{ $p->nombreDia() }} {{ $p->horaCorta() }}</span>
                                </li>
                            @endforeach
                            @if ($misPichangas->count() > 3)
                                <li class="text-gray-400">y {{ $misPichangas->count() - 3 }} más</li>
                            @endif
                        </ul>
                    @else
                        <p class="mt-1 flex-1 text-sm text-gray-500">
                            Aún no estás en ninguna pichanga. Crea la tuya o únete con el link que te comparta el organizador.
                        </p>
                    @endif

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <a href="{{ route('pichangas.index') }}"
                           class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Ver mis pichangas
                        </a>
                        <a href="{{ route('pichangas.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Crear pichanga</a>
                    </div>
                </div>
            </div>

            {{-- Panel del DT: su equipo y el link de jugadores --}}
            @if ($equipoDt)
                {{-- Link recién generado --}}
                @if (session('link_invitacion'))
                    <div class="rounded-md bg-indigo-50 p-4">
                        <p class="mb-2 text-sm font-medium text-indigo-800">
                            Link de jugadores generado — compártelo con tu plantel:
                        </p>
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

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            @if ($equipoDt->escudo)
                                <img src="{{ asset($equipoDt->escudo) }}?t={{ $equipoDt->updated_at?->timestamp }}"
                                     alt="Escudo" class="h-10 w-10 rounded object-cover border border-gray-200">
                            @endif
                            <div>
                                <h3 class="text-base font-medium text-gray-900">{{ $equipoDt->nombre }}</h3>
                                <p class="text-sm text-gray-500">
                                    {{ $equipoDt->jugadores()->count() }} / {{ $equipoDt->modalidad->max_jugadores }} jugadores
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('equipos.show', $equipoDt) }}"
                           class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                            Ver mi equipo
                        </a>
                    </div>

                    <div class="mt-5 border-t border-gray-100 pt-5">
                        <p class="mb-3 text-sm text-gray-600">
                            Genera el link de inscripción y compártelo con tus jugadores para que se sumen al equipo.
                        </p>
                        @can('create', [App\Models\Invitacion::class, $equipoDt, 'jugador'])
                            <form method="POST" action="{{ route('invitaciones.store', $equipoDt) }}">
                                @csrf
                                <input type="hidden" name="tipo" value="jugador">
                                <x-primary-button>Generar link de jugadores</x-primary-button>
                            </form>
                        @endcan
                    </div>
                </div>
            @endif

        </div>
    </div>

    <script>
        function copiarLink() {
            const input = document.getElementById('link-invitacion');
            if (!input) return;
            input.select();
            navigator.clipboard.writeText(input.value);
        }
    </script>
</x-app-layout>
