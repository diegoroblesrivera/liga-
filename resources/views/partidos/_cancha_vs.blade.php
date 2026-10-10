<style>
    .vs-cancha { box-sizing: border-box; width: 100%; max-width: 420px; margin: 0 auto; background: #2e9e4f; border: 2px solid rgba(255,255,255,.5); border-radius: 10px; padding: 10px 14px 12px; }
    .vs-team { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 6px 0; }
    .vs-team img { height: 26px; width: 26px; object-fit: cover; border-radius: 5px; background: #fff; }
    .vs-team span { color: #fff; font-weight: 700; font-size: 14px; text-shadow: 0 1px 2px rgba(0,0,0,.4); }
    .vs-empty { color: #eaffea; font-size: 11px; text-align: center; opacity: .85; padding: 2px 0; }
    .vs-wrap { position: relative; }
    .vs-mid { position: absolute; top: 50%; left: 0; right: 0; border-top: 2px solid rgba(255,255,255,.35); }
    .vs-circle { position: absolute; top: 50%; left: 50%; width: 86px; height: 86px; border: 2px solid rgba(255,255,255,.35); border-radius: 50%; transform: translate(-50%,-50%); }
    .vs-area { position: absolute; left: 50%; transform: translateX(-50%); width: 58%; height: 60px; border: 2px solid rgba(255,255,255,.35); }
    .vs-area-top { top: 0; border-top: none; }
    .vs-area-bottom { bottom: 0; border-bottom: none; }
    .vs-field { position: relative; display: flex; flex-direction: column; min-height: 660px; }
    .vs-half { flex: 1; display: flex; flex-direction: column; justify-content: space-around; padding: 6px 0; }

    .vs-fila { display: flex; justify-content: space-around; }
    /* Distribución fina (líneas de 2, típico de futbolito) */
    .vs-def { justify-content: center; gap: 22px; }               /* defensas juntos, al centro-atrás */
    .vs-mid { justify-content: space-between; padding: 0 4px; }    /* mediocampistas abiertos a las bandas */
    .vs-att { justify-content: center; gap: 64px; }               /* delanteros al centro, juntos pero no tanto */

    .vs-jug { width: 66px; text-align: center; padding-bottom: 4px; }
    .vs-shirt svg { width: 38px; height: auto; display: block; margin: 0 auto; overflow: visible; }
    .vs-nombre { color: #fff; font-size: 11px; font-weight: 600; line-height: 1.3; margin-top: 2px; text-shadow: 0 1px 2px rgba(0,0,0,.45); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
</style>

@php
    // Clase de cada línea según su rol (solo afina líneas de 2; el resto va parejo).
    $filaClase = function ($idx, $total, $count) {
        if ($count === 2) {
            if ($idx === 0) return 'vs-fila vs-def';
            if ($idx === $total - 1) return 'vs-fila vs-att';
            return 'vs-fila vs-mid';
        }
        return 'vs-fila';
    };

    $visLineas = $alVisita['lineas'];
    $visTotal = count($visLineas);
    $locLineas = $alLocal['lineas'];
    $locTotal = count($locLineas);
@endphp

<div id="cancha-vs" class="vs-cancha">
    {{-- Visita arriba --}}
    <div class="vs-team">
        @if ($visita->escudo)
            <img src="{{ asset($visita->escudo) }}?t={{ $visita->updated_at?->timestamp }}" alt="">
        @endif
        <span>{{ $visita->nombre }}</span>
    </div>
    @unless ($alVisita['hayConvocatoria'])
        <div class="vs-empty">Sin convocatoria aún</div>
    @endunless

    <div class="vs-wrap">
        <div class="vs-area vs-area-top"></div>
        <div class="vs-area vs-area-bottom"></div>
        <div class="vs-mid"></div>
        <div class="vs-circle"></div>

        <div class="vs-field">
            {{-- Mitad superior: visita (su arco arriba) --}}
            <div class="vs-half">
                <div class="vs-fila">
                    @include('partidos._shirt', ['jug' => $alVisita['arquero'], 'color' => $visita->color_camiseta])
                </div>
                @foreach ($visLineas as $idx => $linea)
                    <div class="{{ $filaClase($idx, $visTotal, count($linea)) }}">
                        @foreach ($linea as $j)
                            @include('partidos._shirt', ['jug' => $j, 'color' => $visita->color_camiseta])
                        @endforeach
                    </div>
                @endforeach
            </div>

            {{-- Mitad inferior: local (su arco abajo) --}}
            <div class="vs-half">
                @for ($idx = $locTotal - 1; $idx >= 0; $idx--)
                    <div class="{{ $filaClase($idx, $locTotal, count($locLineas[$idx])) }}">
                        @foreach ($locLineas[$idx] as $j)
                            @include('partidos._shirt', ['jug' => $j, 'color' => $local->color_camiseta])
                        @endforeach
                    </div>
                @endfor
                <div class="vs-fila">
                    @include('partidos._shirt', ['jug' => $alLocal['arquero'], 'color' => $local->color_camiseta])
                </div>
            </div>
        </div>
    </div>

    {{-- Local abajo --}}
    <div class="vs-team">
        @if ($local->escudo)
            <img src="{{ asset($local->escudo) }}?t={{ $local->updated_at?->timestamp }}" alt="">
        @endif
        <span>{{ $local->nombre }}</span>
    </div>
    @unless ($alLocal['hayConvocatoria'])
        <div class="vs-empty">Sin convocatoria aún</div>
    @endunless
</div>
