{{--
    Póster del partido, pensado para descargarse como PNG con html2canvas
    (mismo patrón que partidos/_cancha_vs.blade.php).

    Espera recibir $partido con estas relaciones cargadas:
        torneo, equipoLocal, equipoVisita, cancha

    Campo de escudo del EQUIPO ya confirmado: columna `escudo` en la tabla
    `equipos` (varchar, ruta tipo "uploads/equipos/archivo.png"), servida con
    asset(), igual que $pago->comprobante_path, porque este hosting no tiene
    el symlink de storage/ y los archivos viven directo bajo public_html.

    El escudo del TORNEO (logo de la liga) todavía es un supuesto: se usa
    $torneo->escudo_path. Si tu tabla `torneos` usa otro nombre de columna
    (o no tiene escudo todavía), ajusta esa única línea o déjala así: sin
    columna, el póster simplemente muestra un círculo genérico "LIGA".

    Tamaño fijo (480x720, proporción 2:3) para que la imagen final sea
    siempre nítida y consistente, sin importar el tamaño de pantalla del
    que la descarga. La pantalla la muestra escalada; html2canvas la
    captura a tamaño completo (ver script de descarga en show.blade.php).
--}}
@php
    $torneo = $partido->torneo;
    $local = $partido->equipoLocal;
    $visita = $partido->equipoVisita;

    $fechaTexto = $partido->fecha_hora
        ? \Illuminate\Support\Str::upper($partido->fecha_hora->translatedFormat('l d \d\e F Y'))
        : 'FECHA POR CONFIRMAR';

    $horaTexto = $partido->fecha_hora ? $partido->fecha_hora->format('H:i') : '--:--';
    $diaTexto = $partido->fecha_hora ? \Illuminate\Support\Str::upper($partido->fecha_hora->translatedFormat('l')) : 'DIA POR CONFIRMAR';
    $fechaCorta = $partido->fecha_hora ? \Illuminate\Support\Str::upper($partido->fecha_hora->translatedFormat('d \d\e F Y')) : '';

    // Iniciales para el escudo de respaldo cuando el equipo no tiene imagen cargada.
    $inicialesLocal = \Illuminate\Support\Str::of($local->nombre)->explode(' ')->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
    $inicialesVisita = \Illuminate\Support\Str::of($visita->nombre)->explode(' ')->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
@endphp

<div id="poster-canvas" style="position:relative; width:480px; height:720px; overflow:hidden; font-family: Arial, Helvetica, sans-serif; color:#ffffff; background: radial-gradient(circle at 18% 8%, rgba(34,197,94,0.28), transparent 42%), radial-gradient(circle at 85% 92%, rgba(34,197,94,0.22), transparent 42%), linear-gradient(160deg, #0a0f1a 0%, #101c2e 55%, #0a0f1a 100%);">

    {{-- Franjas verdes decorativas en las esquinas --}}
    <div style="position:absolute; top:-36px; left:-70px; width:190px; height:56px; background:#22c55e; transform:rotate(-35deg); opacity:.9;"></div>
    <div style="position:absolute; bottom:-36px; right:-70px; width:230px; height:64px; background:#22c55e; transform:rotate(-35deg); opacity:.9;"></div>

    {{-- Encabezado: logo de liga + tagline --}}
    <div style="position:relative; padding:22px 22px 0 22px; display:flex; align-items:flex-start; justify-content:space-between;">
        <div style="display:flex; align-items:center; gap:10px; max-width:220px;">
            @if (!empty($torneo?->escudo_path))
                <img src="{{ asset($torneo->escudo_path) }}" crossorigin="anonymous"
                     style="width:52px; height:52px; border-radius:9999px; object-fit:cover; border:2px solid #22c55e; flex-shrink:0;">
            @else
                <div style="width:52px; height:52px; border-radius:9999px; background:#111827; border:2px solid #22c55e; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:9px; font-weight:700; letter-spacing:.04em;">
                    LIGA
                </div>
            @endif
            <div style="font-size:11px; font-weight:800; letter-spacing:.03em; line-height:1.2; text-transform:uppercase;">
                {{ $torneo->nombre ?? 'Liga' }}
            </div>
        </div>

        <div style="text-align:right; font-size:9px; line-height:1.6; color:#d1fae5; text-transform:uppercase; letter-spacing:.05em; white-space:nowrap;">
            Futbol<br>Amistad<br>Respeto<br>Buen juego
        </div>
    </div>

    {{-- Fecha grande --}}
    <div style="position:relative; padding:2px 22px 0 22px;">
        <div style="font-size:46px; font-weight:900; font-style:italic; line-height:1; letter-spacing:-.01em;">
            FECHA {{ $partido->numero_fecha }}
        </div>

        <div style="display:inline-block; margin-top:10px; background:#22c55e; color:#052e16; font-size:11px; font-weight:800; padding:4px 10px; letter-spacing:.03em;">
            {{ $fechaTexto }}
        </div>

        <div style="margin-top:8px; font-size:11px; letter-spacing:.08em; color:#9ca3af; text-transform:uppercase;">
            Misma pasion, mas futbol
        </div>
    </div>

    {{-- Cintillo de cancha --}}
    <div style="position:relative; margin:20px 0 0 22px; display:inline-block; background:#22c55e; color:#052e16; padding:6px 16px; font-size:15px; font-weight:900; letter-spacing:.02em; clip-path: polygon(0 0, 100% 0, 94% 100%, 0% 100%);">
        CANCHA {{ $partido->cancha?->nombre ?? '-' }}
    </div>

    {{-- Equipos --}}
    <div style="position:relative; margin-top:28px; padding:0 20px; display:flex; align-items:center; justify-content:center; gap:14px;">

        <div style="width:150px; text-align:center;">
            @if (!empty($local->escudo))
                <img src="{{ asset($local->escudo) }}" crossorigin="anonymous"
                     style="width:120px; height:120px; border-radius:9999px; object-fit:cover; margin:0 auto; display:block; border:3px solid #f3f4f6;">
            @else
                <div style="width:120px; height:120px; border-radius:9999px; margin:0 auto; background:#1f2937; border:3px solid #f3f4f6; display:flex; align-items:center; justify-content:center; font-size:30px; font-weight:900;">
                    {{ $inicialesLocal }}
                </div>
            @endif
            <div style="margin-top:12px; font-size:15px; font-weight:900; text-transform:uppercase; line-height:1.2;">
                {{ $local->nombre }}
            </div>
        </div>

        <div style="font-size:34px; font-weight:900; font-style:italic; color:#e5e7eb; flex-shrink:0;">
            VS
        </div>

        <div style="width:150px; text-align:center;">
            @if (!empty($visita->escudo))
                <img src="{{ asset($visita->escudo) }}" crossorigin="anonymous"
                     style="width:120px; height:120px; border-radius:9999px; object-fit:cover; margin:0 auto; display:block; border:3px solid #f3f4f6;">
            @else
                <div style="width:120px; height:120px; border-radius:9999px; margin:0 auto; background:#1f2937; border:3px solid #f3f4f6; display:flex; align-items:center; justify-content:center; font-size:30px; font-weight:900;">
                    {{ $inicialesVisita }}
                </div>
            @endif
            <div style="margin-top:12px; font-size:15px; font-weight:900; text-transform:uppercase; line-height:1.2;">
                {{ $visita->nombre }}
            </div>
        </div>
    </div>

    {{-- Barra de datos: dia/fecha, hora, cancha --}}
    <div style="position:relative; margin:34px 22px 0 22px; border:1px solid #22c55e; border-radius:10px; padding:14px 12px; display:flex; align-items:center; justify-content:space-between; font-size:11px;">

        <div style="display:flex; align-items:center; gap:8px; flex:1;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2">
                <rect x="3" y="5" width="18" height="16" rx="2"/>
                <path d="M8 3v4M16 3v4M3 10h18"/>
            </svg>
            <div style="line-height:1.3;">
                <div style="font-weight:800; text-transform:uppercase;">{{ $diaTexto }}</div>
                <div style="color:#9ca3af;">{{ $fechaCorta }}</div>
            </div>
        </div>

        <div style="width:1px; height:32px; background:#374151; margin:0 8px;"></div>

        <div style="display:flex; align-items:center; gap:8px; flex:1;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2">
                <circle cx="12" cy="12" r="9"/>
                <path d="M12 7v5l3 3"/>
            </svg>
            <div style="line-height:1.3;">
                <div style="font-weight:800;">{{ $horaTexto }}</div>
                <div style="color:#9ca3af;">Horas</div>
            </div>
        </div>

        <div style="width:1px; height:32px; background:#374151; margin:0 8px;"></div>

        <div style="display:flex; align-items:center; gap:8px; flex:1;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2">
                <rect x="3" y="4" width="18" height="16" rx="1"/>
                <circle cx="12" cy="12" r="3"/>
                <path d="M3 8h18M3 16h18"/>
            </svg>
            <div style="line-height:1.3;">
                <div style="font-weight:800;">CANCHA {{ $partido->cancha?->nombre ?? '-' }}</div>
                <div style="color:#9ca3af;">{{ $torneo?->modalidad?->nombre ?? '' }}</div>
            </div>
        </div>
    </div>

    {{-- Pie --}}
    <div style="position:absolute; bottom:18px; left:22px; font-size:10px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:#e5e7eb; line-height:1.3;">
        El futbol<br>nos une
    </div>
    <div style="position:absolute; bottom:18px; right:22px; font-size:10px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:#e5e7eb; line-height:1.3; text-align:right;">
        Grandes<br>partidos
    </div>
</div>
