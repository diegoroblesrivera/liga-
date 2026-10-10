<x-publico-layout>

    <style>
        .tp-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .tp { table-layout: fixed; border-collapse: separate; border-spacing: 0; min-width: 520px; width: 100%; font-size: .8125rem; }
        .tp th, .tp td { box-sizing: border-box; padding: .5rem .45rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .tp thead th { background: #f9fafb; color: #6b7280; font-size: .66rem; text-transform: uppercase; letter-spacing: .04em; border-bottom: 1px solid #e5e7eb; }
        .tp tbody td { border-bottom: 1px solid #f3f4f6; color: #374151; }
        .tp tbody tr:last-child td { border-bottom: 0; }
        .tp .l { text-align: left; }
        .tp .c { text-align: center; }
        .tp .fija { position: sticky; background: #fff; z-index: 1; }
        .tp thead .fija { background: #f9fafb; z-index: 2; }
        .tp .c1 { left: 0; }
        .tp .c2 { left: 34px; }
        .tp .c3 { left: 158px; }
        .tp .c4 { left: 202px; box-shadow: 6px 0 6px -4px rgba(0, 0, 0, .12); }
        .tp .eq { font-weight: 500; color: #111827; }
        .tp .pos { color: #9ca3af; }
        .tp .pts { font-weight: 600; color: #111827; }

        /* Carrusel de jornadas */
        .jr-vp { overflow: hidden; touch-action: pan-y; }
        .jr-track { display: flex; will-change: transform; }
        .jr-slide { flex: 0 0 100%; min-width: 100%; }
        .jr-arrow { user-select: none; cursor: pointer; padding: .25rem .75rem; font-size: 1.125rem; line-height: 1; border-radius: .375rem; }
        .jr-arrow.on { color: #4f46e5; }
        .jr-arrow.on:hover { background: #eef2ff; }
        .jr-arrow.off { color: #d1d5db; cursor: default; }
    </style>

    <x-slot name="title">{{ $liga->nombre }}</x-slot>

    <div class="mb-6">
        <a href="{{ route('home') }}" class="text-sm text-gray-500 hover:text-gray-700">← Todos los campeonatos</a>
        <h1 class="titulo-deportivo mt-1 text-3xl text-balon">🏆 {{ $liga->nombre }}</h1>
        @if ($liga->descripcion)
            <p class="mt-1 text-gray-600">{{ $liga->descripcion }}</p>
        @endif
    </div>

    @if ($torneos->count() > 1)
        <form method="GET" class="mb-6">
            <label for="torneo" class="mr-2 text-sm text-gray-600">Torneo:</label>
            <select id="torneo" name="torneo" onchange="this.form.submit()"
                    class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach ($torneos as $t)
                    <option value="{{ $t->id }}" @selected($torneo && $torneo->id === $t->id)>{{ $t->nombre }}</option>
                @endforeach
            </select>
        </form>
    @endif

    @if (! $torneo)
        <div class="rounded-lg bg-white p-8 text-center text-gray-500 shadow-sm">
            Todavía no hay torneos para mostrar.
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- Columna izquierda: tabla de posiciones --}}
            <div class="lg:col-span-2">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <h2 class="border-b border-gray-100 px-5 py-3 text-sm font-semibold text-gray-800">
                        Tabla de posiciones · {{ $torneo->nombre }}
                    </h2>
                    <div class="tp-wrap">
                        <table class="tp">
                            <colgroup>
                                <col style="width:34px"><col style="width:124px"><col style="width:44px"><col style="width:44px">
                                <col style="width:44px"><col style="width:44px"><col style="width:44px"><col style="width:44px">
                                <col style="width:48px"><col style="width:52px">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="fija c1 l">#</th>
                                    <th class="fija c2 l">Equipo</th>
                                    <th class="fija c3 c">PJ</th>
                                    <th class="fija c4 c">PG</th>
                                    <th class="c">PE</th><th class="c">PP</th><th class="c">GF</th>
                                    <th class="c">GC</th><th class="c">Dif</th><th class="c">Pts</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($posiciones as $i => $fila)
                                    <tr>
                                        <td class="fija c1 l pos">{{ $i + 1 }}</td>
                                        <td class="fija c2 l eq">{{ $fila->equipo }}</td>
                                        <td class="fija c3 c">{{ $fila->pj }}</td>
                                        <td class="fija c4 c">{{ $fila->pg }}</td>
                                        <td class="c">{{ $fila->pe }}</td>
                                        <td class="c">{{ $fila->pp }}</td>
                                        <td class="c">{{ $fila->gf }}</td>
                                        <td class="c">{{ $fila->gc }}</td>
                                        <td class="c">{{ $fila->dif }}</td>
                                        <td class="c pts">{{ $fila->pts }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="c" style="color:#6b7280; padding:1.25rem;">
                                            Aún no hay partidos finalizados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Columna derecha: jornadas deslizables + goleadores --}}
            <div class="space-y-6">

                {{-- Carrusel de jornadas --}}
                <div id="jornadas" class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                     data-inicial="{{ $indiceInicial }}">
                    <div class="flex items-center justify-between border-b border-gray-100 px-3 py-2">
                        <span id="jr-prev" class="jr-arrow off" aria-label="Fecha anterior">‹</span>
                        <span id="jr-titulo" class="text-sm font-semibold text-gray-800">—</span>
                        <span id="jr-next" class="jr-arrow off" aria-label="Fecha siguiente">›</span>
                    </div>

                    @if ($jornadas->isEmpty())
                        <p class="px-5 py-4 text-sm text-gray-500">Todavía no hay partidos programados.</p>
                    @else
                        <div class="jr-vp">
                            <div class="jr-track" id="jr-track">
                                @foreach ($jornadas as $j)
                                    <div class="jr-slide" data-numero="{{ $j['numero'] }}">
                                        <div class="divide-y divide-gray-100">
                                            @foreach ($j['partidos'] as $partido)
                                                <div class="px-5 py-3 text-sm">
                                                    <div class="flex items-center justify-between">
                                                        <span class="font-medium text-gray-900">{{ $partido->equipoLocal->nombre }}</span>
                                                        @if ($partido->estado === 'finalizado')
                                                            <span class="marcador text-2xl text-balon">{{ $partido->goles_local }} - {{ $partido->goles_visita }}</span>
                                                        @else
                                                            <span class="text-xs text-gray-400">vs</span>
                                                        @endif
                                                        <span class="font-medium text-gray-900">{{ $partido->equipoVisita->nombre }}</span>
                                                    </div>
                                                    <div class="mt-1 text-xs text-gray-500">
                                                        {{ $partido->fecha_hora?->format('d/m/Y H:i') ?? 'Sin fecha' }}
                                                        @if ($partido->cancha) · {{ $partido->cancha->nombre }} @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <p class="px-5 py-2 text-center text-xs text-gray-400">Desliza o usa las flechas para cambiar de fecha.</p>
                    @endif
                </div>

                {{-- Goleadores --}}
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <h2 class="border-b border-gray-100 px-5 py-3 text-sm font-semibold text-gray-800">Goleadores</h2>
                    <div class="divide-y divide-gray-100">
                        @forelse ($goleadores as $i => $g)
                            <div class="flex items-center justify-between px-5 py-2 text-sm">
                                <span class="text-gray-900">
                                    <span class="mr-2 text-gray-400">{{ $i + 1 }}</span>
                                    {{ $g->jugador }}
                                    <span class="text-gray-400">· {{ $g->equipo }}</span>
                                </span>
                                <span class="marcador text-2xl text-cancha-600">{{ $g->goles }}</span>
                            </div>
                        @empty
                            <p class="px-5 py-4 text-sm text-gray-500">Todavía no hay goles registrados.</p>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>
    @endif

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

                // Decidir en el primer movimiento si el gesto es horizontal o vertical.
                if (!decidido) {
                    decidido = true;
                    horizontal = Math.abs(dx) > Math.abs(dy);
                }
                if (!horizontal) return; // dejar el scroll vertical normal

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
                    ir(index, true); // volver a su lugar
                }
            });

            window.addEventListener('resize', function () { pintar(false); });

            // Estado inicial
            ir(index, false);
        })();
    </script>
</x-publico-layout>
