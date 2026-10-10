<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Alineación · {{ $equipo->nombre }}</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('equipos.show', $equipo) }}"
                   class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Volver
                </a>
                <button type="button" onclick="descargarAlineacion(this)"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    Descargar imagen
                </button>
            </div>
        </div>
    </x-slot>

    <style>
        .lu-cancha { box-sizing: border-box; width: 100%; max-width: 420px; margin: 0 auto; background: #2e9e4f; border: 2px solid rgba(255,255,255,.5); border-radius: 10px; padding: 16px 14px 20px; }
        .lu-header { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 4px 0 10px; }
        .lu-header img { height: 30px; width: 30px; object-fit: cover; border-radius: 5px; background: #fff; }
        .lu-header span { color: #fff; font-weight: 700; font-size: 16px; text-shadow: 0 1px 2px rgba(0,0,0,.4); }

        .lu-wrap { position: relative; }
        /* Línea de medio campo y círculo central */
        .lu-mid { position: absolute; top: 50%; left: 0; right: 0; border-top: 2px solid rgba(255,255,255,.35); }
        .lu-circle { position: absolute; top: 50%; left: 50%; width: 92px; height: 92px; border: 2px solid rgba(255,255,255,.35); border-radius: 50%; transform: translate(-50%,-50%); }
        /* Áreas grandes */
        .lu-area { position: absolute; left: 50%; transform: translateX(-50%); width: 58%; height: 66px; border: 2px solid rgba(255,255,255,.35); }
        .lu-area-top { top: 0; border-top: none; }
        .lu-area-bottom { bottom: 0; border-bottom: none; }
        /* Áreas chicas */
        .lu-box { position: absolute; left: 50%; transform: translateX(-50%); width: 32%; height: 30px; border: 2px solid rgba(255,255,255,.35); }
        .lu-box-top { top: 0; border-top: none; }
        .lu-box-bottom { bottom: 0; border-bottom: none; }

        .lu-field { position: relative; display: flex; flex-direction: column; justify-content: space-between; min-height: 580px; padding: 10px 0 14px; }
        .lu-fila { display: flex; justify-content: space-around; }
        .lu-jug { width: 76px; text-align: center; padding-bottom: 6px; }
        .lu-shirt svg { width: 46px; height: auto; display: block; margin: 0 auto; overflow: visible; }
        .lu-nombre { color: #fff; font-size: 12px; font-weight: 600; line-height: 1.35; margin-top: 2px; padding-bottom: 2px; text-shadow: 0 1px 2px rgba(0,0,0,.45); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    </style>

    <div class="py-8">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">

            <div id="cancha" class="lu-cancha">
                <div class="lu-header">
                    @if ($equipo->escudo)
                        <img src="{{ asset($equipo->escudo) }}?t={{ $equipo->updated_at?->timestamp }}" alt="Escudo">
                    @endif
                    <span>{{ $equipo->nombre }}</span>
                </div>

                <div class="lu-wrap">
                    <div class="lu-area lu-area-top"></div>
                    <div class="lu-box lu-box-top"></div>
                    <div class="lu-area lu-area-bottom"></div>
                    <div class="lu-box lu-box-bottom"></div>
                    <div class="lu-mid"></div>
                    <div class="lu-circle"></div>

                    <div class="lu-field">
                        @foreach (array_reverse($lineas) as $linea)
                            <div class="lu-fila">
                                @foreach ($linea as $jug)
                                    @include('equipos._camiseta', ['jug' => $jug, 'color' => $equipo->color_camiseta])
                                @endforeach
                            </div>
                        @endforeach

                        <div class="lu-fila">
                            @include('equipos._camiseta', ['jug' => $arquero, 'color' => $equipo->color_camiseta])
                        </div>
                    </div>
                </div>
            </div>

            <p class="mx-auto mt-4 text-center text-xs text-gray-400">
                El arquero se ubica automáticamente si algún jugador tiene esa posición. Los puestos sin jugador quedan vacíos.
            </p>

        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        function descargarAlineacion(btn) {
            const cancha = document.getElementById('cancha');
            if (!cancha || typeof html2canvas === 'undefined') return;

            if (btn) { btn.disabled = true; btn.textContent = 'Generando…'; }

            // Medidas exactas + un margen para evitar que html2canvas recorte los bordes.
            const rect = cancha.getBoundingClientRect();
            const w = Math.ceil(rect.width) + 2;
            const h = Math.ceil(cancha.scrollHeight) + 2;

            html2canvas(cancha, {
                useCORS: true,
                backgroundColor: null,
                scale: 2,
                width: w,
                height: h,
                windowWidth: document.documentElement.scrollWidth,
                scrollX: 0,
                scrollY: -window.scrollY
            }).then(function (canvas) {
                const a = document.createElement('a');
                a.href = canvas.toDataURL('image/png');
                a.download = 'alineacion-{{ \Illuminate\Support\Str::slug($equipo->nombre) }}.png';
                a.click();
            }).finally(function () {
                if (btn) { btn.disabled = false; btn.textContent = 'Descargar imagen'; }
            });
        }
    </script>
</x-app-layout>
