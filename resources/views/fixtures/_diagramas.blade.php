{{-- Diagramas de ejemplo de cada formato (se muestran según la opción elegida, con Alpine: tipo y etapas) --}}
@php
    $verde = '#369A4D';
    $verdeClaro = '#B0DFBA';
    $oscuro = '#1F2A2E';
    $gris = '#9CA3AF';

    // Todos contra todos: 6 equipos en círculo, unidos todos con todos.
    $n = 6;
    $cx = 110;
    $cy = 100;
    $r = 72;
    $nodos = [];
    for ($i = 0; $i < $n; $i++) {
        $a = -M_PI / 2 + $i * 2 * M_PI / $n;
        $nodos[] = [round($cx + $r * cos($a), 1), round($cy + $r * sin($a), 1)];
    }

    // Grupo de 4 (para la fase de grupos): cuadrado con sus diagonales.
    $grupo = fn ($x, $y) => [[$x, $y], [$x + 60, $y], [$x + 60, $y + 60], [$x, $y + 60]];

    // Llave tipo Mundial: 4 equipos a la izquierda y 4 a la derecha, la copa al medio.
    $ancho = 560;
    $equiposY = [30, 80, 140, 190];
    $semiY = [55, 165];
    $finalY = 110;
@endphp

<div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Así se juega</p>

    {{-- Todos contra todos --}}
    <div x-show="tipo === 'liga'" class="flex flex-wrap items-center justify-center gap-4">
        <svg viewBox="0 0 220 200" class="h-44 w-auto" role="img" aria-label="Todos contra todos: cada equipo juega con todos los demás">
            @foreach ($nodos as $i => [$x1, $y1])
                @foreach ($nodos as $j => [$x2, $y2])
                    @if ($j > $i)
                        <line x1="{{ $x1 }}" y1="{{ $y1 }}" x2="{{ $x2 }}" y2="{{ $y2 }}" stroke="{{ $verdeClaro }}" stroke-width="1.6" />
                    @endif
                @endforeach
            @endforeach
            @foreach ($nodos as $i => [$x, $y])
                <circle cx="{{ $x }}" cy="{{ $y }}" r="15" fill="{{ $verde }}" stroke="#fff" stroke-width="3" />
                <text x="{{ $x }}" y="{{ $y + 4 }}" text-anchor="middle" font-size="11" font-weight="700" fill="#fff">E{{ $i + 1 }}</text>
            @endforeach
        </svg>

        {{-- Con 2 etapas: después pasan los mejores a la llave --}}
        <template x-if="etapas === 2">
            <div class="flex items-center gap-3">
                <div class="text-center text-xs text-gray-600">
                    <div class="text-2xl text-gray-400">➜</div>
                    los mejores<br>de la tabla
                </div>
                <svg viewBox="0 0 150 120" class="h-28 w-auto" role="img" aria-label="Luego eliminación directa">
                    @foreach ([[10, 15], [10, 45], [10, 75], [10, 105]] as $k => [$x, $y])
                        <rect x="{{ $x }}" y="{{ $y - 9 }}" width="40" height="18" rx="4" fill="#fff" stroke="{{ $verde }}" />
                        <text x="{{ $x + 20 }}" y="{{ $y + 4 }}" text-anchor="middle" font-size="9" fill="{{ $oscuro }}">{{ [1, 4, 2, 3][$k] }}°</text>
                    @endforeach
                    <path d="M50 15 H62 V45 H50 M62 30 H74 M50 75 H62 V105 H50 M62 90 H74 M74 30 V90 M74 60 H90" fill="none" stroke="{{ $gris }}" stroke-width="1.5" />
                    <text x="115" y="70" text-anchor="middle" font-size="34">🏆</text>
                </svg>
            </div>
        </template>
    </div>

    {{-- Fase de grupos + play offs --}}
    <div x-show="tipo === 'grupos'" class="flex justify-center">
        <svg viewBox="0 0 430 215" class="h-48 w-full max-w-xl" role="img" aria-label="Grupos que juegan todos contra todos y luego play offs">
            @foreach (['A' => [20, 42], 'B' => [20, 142]] as $letra => [$gx, $gy])
                @php
                    $p = $grupo($gx + 15, $gy - 10);
                @endphp
                <rect x="{{ $gx }}" y="{{ $gy - 22 }}" width="90" height="82" rx="10" fill="#fff" stroke="{{ $verdeClaro }}" />
                <text x="{{ $gx + 45 }}" y="{{ $gy - 26 }}" text-anchor="middle" font-size="10" font-weight="700" fill="{{ $oscuro }}">Grupo {{ $letra }}</text>
                @foreach ($p as $i => [$x1, $y1])
                    @foreach ($p as $j => [$x2, $y2])
                        @if ($j > $i)
                            <line x1="{{ $x1 }}" y1="{{ $y1 }}" x2="{{ $x2 }}" y2="{{ $y2 }}" stroke="{{ $verdeClaro }}" stroke-width="1.4" />
                        @endif
                    @endforeach
                @endforeach
                @foreach ($p as $i => [$x, $y])
                    <circle cx="{{ $x }}" cy="{{ $y }}" r="9" fill="{{ $i < 2 ? $verde : $gris }}" stroke="#fff" stroke-width="2" />
                @endforeach
            @endforeach

            <text x="155" y="114" text-anchor="middle" font-size="22" fill="{{ $gris }}">➜</text>
            <text x="155" y="132" text-anchor="middle" font-size="8" fill="#6B7280">clasifican</text>

            @foreach ([['1° A', 50], ['2° B', 85], ['1° B', 135], ['2° A', 170]] as [$txt, $y])
                <rect x="190" y="{{ $y - 11 }}" width="58" height="22" rx="5" fill="#fff" stroke="{{ $verde }}" />
                <text x="219" y="{{ $y + 4 }}" text-anchor="middle" font-size="10" font-weight="600" fill="{{ $oscuro }}">{{ $txt }}</text>
            @endforeach
            <path d="M248 50 H262 V85 H248 M262 67 H285 M248 135 H262 V170 H248 M262 152 H285 M285 67 V152 M285 110 H305" fill="none" stroke="{{ $gris }}" stroke-width="1.6" />
            <text x="268" y="62" font-size="8" fill="#6B7280">semi</text>
            <text x="268" y="147" font-size="8" fill="#6B7280">semi</text>
            <text x="350" y="122" text-anchor="middle" font-size="44">🏆</text>
            <text x="350" y="150" text-anchor="middle" font-size="9" font-weight="700" fill="{{ $oscuro }}">Final</text>
        </svg>
    </div>

    {{-- Eliminación directa: llave tipo Mundial con la copa al medio --}}
    <div x-show="tipo === 'eliminacion'" class="flex justify-center">
        <svg viewBox="0 0 {{ $ancho }} 220" class="h-52 w-full max-w-2xl" role="img" aria-label="Eliminación directa: equipos a la izquierda y a la derecha, la copa al medio">
            @foreach ([['izq', 0], ['der', 1]] as [$lado, $k])
                @php
                    $espejo = fn ($x) => $k ? $ancho - $x : $x; // el lado derecho es el reflejo del izquierdo
                    $ancla = $k ? 'end' : 'start';
                @endphp
                {{-- Cuartos --}}
                @foreach ($equiposY as $i => $y)
                    <rect x="{{ $k ? $ancho - 92 : 12 }}" y="{{ $y - 12 }}" width="80" height="24" rx="5" fill="#fff" stroke="{{ $verde }}" stroke-width="1.4" />
                    <text x="{{ $k ? $ancho - 52 : 52 }}" y="{{ $y + 4 }}" text-anchor="middle" font-size="10" fill="{{ $oscuro }}">Equipo {{ $k * 4 + $i + 1 }}</text>
                @endforeach
                {{-- Líneas a semifinal --}}
                @foreach ([[0, 1, 0], [2, 3, 1]] as [$a, $b, $s])
                    <path d="M{{ $espejo(92) }} {{ $equiposY[$a] }} H{{ $espejo(112) }} V{{ $equiposY[$b] }} H{{ $espejo(92) }} M{{ $espejo(112) }} {{ $semiY[$s] }} H{{ $espejo(132) }}" fill="none" stroke="{{ $gris }}" stroke-width="1.6" />
                    <rect x="{{ $k ? $ancho - 202 : 132 }}" y="{{ $semiY[$s] - 11 }}" width="70" height="22" rx="5" fill="#fff" stroke="{{ $verdeClaro }}" stroke-dasharray="3 2" />
                    <text x="{{ $k ? $ancho - 167 : 167 }}" y="{{ $semiY[$s] + 4 }}" text-anchor="middle" font-size="9" fill="#6B7280">Ganador</text>
                @endforeach
                {{-- Semifinal a la final --}}
                <path d="M{{ $espejo(202) }} {{ $semiY[0] }} H{{ $espejo(222) }} V{{ $semiY[1] }} H{{ $espejo(202) }} M{{ $espejo(222) }} {{ $finalY }} H{{ $espejo(240) }}" fill="none" stroke="{{ $gris }}" stroke-width="1.6" />
                <text x="{{ $espejo(52) }}" y="12" text-anchor="middle" font-size="9" font-weight="700" fill="#6B7280">CUARTOS</text>
                <text x="{{ $espejo(167) }}" y="12" text-anchor="middle" font-size="9" font-weight="700" fill="#6B7280">SEMIFINAL</text>
            @endforeach
            <rect x="240" y="{{ $finalY - 12 }}" width="80" height="24" rx="12" fill="{{ $verde }}" />
            <text x="280" y="{{ $finalY + 4 }}" text-anchor="middle" font-size="10" font-weight="700" fill="#fff">FINAL</text>
            <text x="280" y="{{ $finalY - 26 }}" text-anchor="middle" font-size="46">🏆</text>
            <text x="280" y="{{ $finalY + 100 }}" text-anchor="middle" font-size="9" fill="#6B7280">El que pierde, queda fuera</text>
        </svg>
    </div>

    <p class="mt-2 text-center text-xs text-gray-500"
       x-text="{
           liga: etapas === 2 ? 'Todos juegan contra todos; los mejores de la tabla pasan a la eliminación directa.' : 'Cada equipo juega contra todos los demás. Gana el que suma más puntos.',
           grupos: 'Cada grupo juega todos contra todos; los mejores pasan a los play offs y se cruzan 1° de un grupo contra 2° de otro.',
           eliminacion: 'Partido único: el ganador avanza y el que pierde queda fuera. Si empatan, penales.',
       }[tipo]"></p>
</div>
