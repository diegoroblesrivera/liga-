{{-- Un cruce de la llave. Recibe: $p (Partido o null si aún no se define) --}}
@if ($p)
    @php
        $ganador = $p->ganadorId();
        $lados = [
            [$p->equipoLocal->nombre, $p->goles_local, $p->penales_local, $p->equipo_local_id],
            [$p->equipoVisita->nombre, $p->goles_visita, $p->penales_visita, $p->equipo_visita_id],
        ];
    @endphp
    <a href="{{ route('partidos.show', $p) }}" class="block overflow-hidden rounded-md border border-gray-200 text-sm hover:border-indigo-300">
        @foreach ($lados as [$nombre, $goles, $penales, $id])
            <div class="flex items-center justify-between gap-2 px-2 py-1.5 {{ $loop->first ? 'border-b border-gray-100' : '' }} {{ $ganador === $id ? 'bg-cancha-50 font-semibold text-gray-900' : ($ganador ? 'text-gray-400' : 'text-gray-700') }}">
                <span class="truncate">{{ $nombre }}</span>
                <span class="shrink-0 tabular-nums">
                    @if ($p->estaFinalizado())
                        {{ $goles }}@if ($penales !== null)<span class="text-xs text-gray-500"> ({{ $penales }})</span>@endif
                    @endif
                </span>
            </div>
        @endforeach
        @unless ($p->estaFinalizado())
            <div class="bg-gray-50 px-2 py-1 text-center text-[11px] text-gray-500">{{ $p->fecha_hora?->format('d/m H:i') ?? 'Sin fecha' }}</div>
        @endunless
    </a>
@else
    <div class="rounded-md border border-dashed border-gray-200 px-2 py-4 text-center text-xs text-gray-400">Por definir</div>
@endif
