{{-- Logo de TuTorneo: pelota + "TU" (verde césped) + "TORNEO" (blanco), en Kanit itálica.
     Pensado para fondos oscuros (menú, encabezados, pie). apilado = pelota arriba y texto abajo. --}}
@props(['apilado' => false, 'pelota' => 'h-7 w-7'])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 '.($apilado ? 'flex-col gap-1' : '')]) }}>
    <svg viewBox="0 0 24 24" class="{{ $pelota }} shrink-0 drop-shadow" aria-hidden="true">
        <circle cx="12" cy="12" r="11" fill="#FFFFFF" stroke="#1F2A2E" stroke-width="1.4"/>
        <polygon points="12,7.3 16.1,10.3 14.5,15.1 9.5,15.1 7.9,10.3" fill="#1F2A2E"/>
        <g stroke="#1F2A2E" stroke-width="1.2" stroke-linecap="round">
            <line x1="12" y1="7.3" x2="12" y2="1.6"/>
            <line x1="16.1" y1="10.3" x2="21.6" y2="8.6"/>
            <line x1="14.5" y1="15.1" x2="17.9" y2="20.6"/>
            <line x1="9.5" y1="15.1" x2="6.1" y2="20.6"/>
            <line x1="7.9" y1="10.3" x2="2.4" y2="8.6"/>
        </g>
    </svg>
    <span class="titulo-deportivo leading-none whitespace-nowrap">
        <span class="text-cancha-400">Tu</span><span class="text-white">Torneo</span>
    </span>
</span>
