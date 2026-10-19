<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Fixture</h2>

            @can('create', App\Models\Partido::class)
                <div class="flex items-center gap-3">
                    <a href="{{ route('fixtures.create') }}"
                       class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Generar fixture
                    </a>
                    <a href="{{ route('partidos.create') }}"
                       class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        Programar partido
                    </a>
                </div>
            @endcan
        </div>
    </x-slot>

    <style>
        /* Carrusel de jornadas */
        .jr-vp { overflow: hidden; touch-action: pan-y; }
        .jr-track { display: flex; will-change: transform; }
        .jr-slide { flex: 0 0 100%; min-width: 100%; }
        .jr-arrow { user-select: none; cursor: pointer; padding: .25rem .75rem; font-size: 1.125rem; line-height: 1; border-radius: .375rem; }
        .jr-arrow.on { color: #4f46e5; }
        .jr-arrow.on:hover { background: #eef2ff; }
        .jr-arrow.off { color: #d1d5db; cursor: default; }
    </style>

    <div class="py-8">
        <div class="mx-auto max-w-2xl space-y-6 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>
            @endif

            @php
                $puedeBorrar = auth()->user()->hasRole('admin');
            @endphp

            <div id="jornadas" class="overflow-hidden bg-white shadow-sm sm:rounded-lg" data-inicial="{{ $indiceInicial }}">
                <div class="flex items-center justify-between border-b border-gray-100 px-3 py-2">
                    <span id="jr-prev" class="jr-arrow off" aria-label="Fecha anterior">&lsaquo;</span>
                    <span id="jr-titulo" class="text-sm font-semibold text-gray-800">&mdash;</span>
                    <span id="jr-next" class="jr-arrow off" aria-label="Fecha siguiente">&rsaquo;</span>
                </div>

                @if ($jornadas->isEmpty())
                    <p class="px-5 py-6 text-sm text-gray-500">Aun no hay partidos programados.</p>
                @else
                    <div class="jr-vp">
                        <div class="jr-track" id="jr-track">
                            @foreach ($jornadas as $j)
                                <div class="jr-slide" data-numero="{{ $j['numero'] }}">
                                    <div class="divide-y divide-gray-100">
                                        @foreach ($j['partidos'] as $partido)
                                            <a href="{{ route('partidos.show', $partido) }}"
                                               class="block px-5 py-4 text-sm hover:bg-gray-50">
                                                <div class="flex items-center justify-between">
                                                    <span class="font-medium text-gray-900">{{ $partido->equipoLocal->nombre }}</span>
                                                    @if ($partido->estaFinalizado())
                                                        <span class="marcador text-2xl text-balon">{{ $partido->goles_local }} - {{ $partido->goles_visita }}</span>
                                                    @else
                                                        <span class="text-xs text-gray-400">vs</span>
                                                    @endif
                                                    <span class="font-medium text-gray-900">{{ $partido->equipoVisita->nombre }}</span>
                                                </div>
                                                <div class="mt-1 text-xs text-gray-500">
                                                    {{ $partido->fecha_hora?->format('d/m/Y H:i') ?? 'Sin fecha' }}
                                                    @if ($partido->cancha) &middot; {{ $partido->cancha->nombre }} @endif
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>

                                    {{-- Lista de asistencia de la fecha (QR) --}}
                                    @if (\App\Support\LigaActual::liga()?->pedir_lista)
                                        @foreach ($j['partidos']->groupBy('torneo_id') as $partidosTorneo)
                                            @if (\App\Support\Lista::puedeGestionarFecha(auth()->user(), $partidosTorneo))
                                                <a href="{{ route('lista.fecha', [$partidosTorneo->first()->torneo_id, $j['numero']]) }}"
                                                   class="block border-t border-gray-100 px-5 py-3 text-sm font-medium text-indigo-600 hover:bg-gray-50 hover:text-indigo-800">
                                                    📋 Lista y QR de la fecha {{ $j['numero'] }}{{ $j['partidos']->pluck('torneo_id')->unique()->count() > 1 ? ' · '.$partidosTorneo->first()->torneo->nombre : '' }}
                                                </a>
                                            @endif
                                        @endforeach
                                    @endif

                                    {{-- Eliminar la fecha completa (por torneo, por si una jornada mezcla torneos) --}}
                                    @if ($puedeBorrar)
                                        @foreach ($j['partidos']->groupBy('torneo_id') as $partidosTorneo)
                                            @php
                                                $torneoFecha = $partidosTorneo->first()->torneo;
                                                $jugados = $partidosTorneo->filter->estaFinalizado()->count();
                                                $aviso = "¿Eliminar la fecha {$j['numero']} de \"{$torneoFecha->nombre}\"? Se borrarán sus {$partidosTorneo->count()} partidos"
                                                    .($jugados ? ", incluidos {$jugados} ya jugados con sus goles, tarjetas y pagos (cambia la tabla de posiciones)" : '')
                                                    .'. No se puede deshacer.';
                                            @endphp
                                            <form method="POST" action="{{ route('partidos.fechas.destroy', [$torneoFecha, $j['numero']]) }}"
                                                  class="border-t border-gray-100 px-5 py-3 text-right"
                                                  data-confirm="{{ $aviso }}" onsubmit="return confirm(this.dataset.confirm)">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-800">
                                                    Eliminar fecha {{ $j['numero'] }}{{ $j['partidos']->pluck('torneo_id')->unique()->count() > 1 ? ' de '.$torneoFecha->nombre : '' }}
                                                </button>
                                            </form>
                                        @endforeach
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <p class="px-5 py-2 text-center text-xs text-gray-400">Desliza o usa las flechas para cambiar de fecha.</p>
                @endif
            </div>

        </div>
    </div>

    <script>
        (function () {
            const cont = document.getElementById('jornadas');
            const track = document.getElementById('jr-track');
            if (!cont || !track) return;

            const vp = track.parentElement;              // .jr-vp
            const slides = Array.from(track.children);   // .jr-slide
            const titulo = document.getElementById('jr-titulo');
            const prev = document.getElementById('jr-prev');
            const next = document.getElementById('jr-next');
            const total = slides.length;
            if (total === 0) return;

            let index = parseInt(cont.dataset.inicial || '0', 10);
            if (isNaN(index) || index < 0 || index >= total) index = 0;

            let dragDx = 0;

            function ancho() { return vp.clientWidth; }

            function pintar(conAnimacion) {
                track.style.transition = conAnimacion ? 'transform .3s ease' : 'none';
                track.style.transform = 'translateX(' + (-index * ancho() + dragDx) + 'px)';
            }

            function encabezado() {
                const num = slides[index].dataset.numero;
                titulo.textContent = 'Fecha ' + num;
                prev.className = 'jr-arrow ' + (index > 0 ? 'on' : 'off');
                next.className = 'jr-arrow ' + (index < total - 1 ? 'on' : 'off');
            }

            function ir(nuevo, animar) {
                index = Math.max(0, Math.min(total - 1, nuevo));
                dragDx = 0;
                pintar(animar);
                encabezado();
            }

            prev.addEventListener('click', function () { if (index > 0) ir(index - 1, true); });
            next.addEventListener('click', function () { if (index < total - 1) ir(index + 1, true); });

            // Gestos táctiles
            let startX = 0, startY = 0, arrastrando = false, decidido = false, horizontal = false;

            vp.addEventListener('touchstart', function (e) {
                startX = e.touches[0].clientX;
                startY = e.touches[0].clientY;
                arrastrando = true;
                decidido = false;
                horizontal = false;
            }, { passive: true });

            vp.addEventListener('touchmove', function (e) {
                if (!arrastrando) return;
                const dx = e.touches[0].clientX - startX;
                const dy = e.touches[0].clientY - startY;

                if (!decidido) {
                    decidido = true;
                    horizontal = Math.abs(dx) > Math.abs(dy);
                }
                if (!horizontal) return;

                dragDx = dx;
                pintar(false);
            }, { passive: true });

            vp.addEventListener('touchend', function () {
                if (!arrastrando) return;
                arrastrando = false;
                if (!horizontal) { dragDx = 0; return; }

                const umbral = ancho() * 0.2;
                if (dragDx <= -umbral && index < total - 1) {
                    ir(index + 1, true);
                } else if (dragDx >= umbral && index > 0) {
                    ir(index - 1, true);
                } else {
                    ir(index, true);
                }
            });

            window.addEventListener('resize', function () { pintar(false); });

            ir(index, false);
        })();
    </script>
</x-app-layout>
