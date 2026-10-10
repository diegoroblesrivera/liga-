<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #111; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .sub { color: #666; font-size: 11px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 5px 6px; text-align: left; }
        th { background: #f2f2f2; font-size: 10px; text-transform: uppercase; }
        td.num { text-align: right; }
        .pagado { color: #15803d; font-weight: bold; }
        .pendiente { color: #b91c1c; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Pagos · {{ $torneo->nombre }}</h1>
    <div class="sub">Generado el {{ now()->format('d/m/Y H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>Jornada</th>
                <th>Fecha y hora</th>
                <th>Partido</th>
                <th>Equipo</th>
                <th>Monto</th>
                <th>Estado</th>
                <th>Fecha de subida</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($filas as $f)
                <tr>
                    <td>{{ $f['jornada'] }}</td>
                    <td>{{ $f['fecha'] }}</td>
                    <td>{{ $f['partido'] }}</td>
                    <td>{{ $f['equipo'] }}</td>
                    <td class="num">${{ number_format($f['monto'], 0, ',', '.') }}</td>
                    <td class="{{ $f['estado'] === 'Pagado' ? 'pagado' : 'pendiente' }}">{{ $f['estado'] }}</td>
                    <td>{{ $f['subida'] }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No hay pagos para este torneo.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
