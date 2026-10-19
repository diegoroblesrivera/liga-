<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lista Fecha {{ $numero }} · {{ $liga->nombre }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; color: #111827; background: #fff; }
        .hoja { max-width: 640px; margin: 0 auto; padding: 32px 24px; text-align: center; }
        h1 { margin: 0; font-size: 34px; letter-spacing: -0.5px; }
        .sub { margin: 6px 0 0; font-size: 18px; color: #4b5563; }
        .qr { width: 420px; max-width: 100%; margin: 28px auto 12px; }
        .qr svg { width: 100%; height: auto; display: block; }
        .paso { font-size: 20px; margin: 8px 0; }
        .partidos { margin: 24px auto 0; padding: 0; list-style: none; font-size: 15px; color: #374151; }
        .partidos li { padding: 4px 0; }
        .marca { margin-top: 28px; font-size: 14px; color: #6b7280; }
        .acciones { margin-top: 24px; }
        .acciones button { font-size: 15px; padding: 10px 18px; border-radius: 8px; border: 0; background: #4f46e5; color: #fff; cursor: pointer; }
        @media print { .acciones { display: none; } .hoja { padding-top: 12px; } }
    </style>
</head>
<body>
    <div class="hoja">
        <h1>📋 Lista · Fecha {{ $numero }}</h1>
        <p class="sub">{{ $liga->nombre }} · {{ $torneo->nombre }}</p>

        <div class="qr">{!! $qr !!}</div>

        <p class="paso"><strong>1.</strong> Abre la cámara del celular y apunta al código</p>
        <p class="paso"><strong>2.</strong> Entra con tu cuenta de {{ config('app.name') }}</p>
        <p class="paso"><strong>3.</strong> ¡Listo, quedaste presente! ✅</p>
        @if ($liga->exigeLista())
            <p class="paso" style="color:#b91c1c"><strong>Sin lista no se juega.</strong></p>
        @endif

        <ul class="partidos">
            @foreach ($partidos as $p)
                <li>{{ $p->fecha_hora?->format('H:i') ?? '—' }} · {{ $p->equipoLocal->nombre }} vs {{ $p->equipoVisita->nombre }}</li>
            @endforeach
        </ul>

        <p class="marca">⚽ {{ config('app.name') }}</p>

        <div class="acciones"><button type="button" onclick="window.print()">🖨️ Imprimir</button></div>
    </div>
    <script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
</body>
</html>
