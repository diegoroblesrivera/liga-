{{-- Mejores insignias junto al nombre. Recibe: $iconos (colección de definiciones o null) --}}
@if (! empty($iconos) && count($iconos))
    <span class="ms-1 inline-flex gap-0.5 align-middle text-sm leading-none">
        @foreach ($iconos as $d)
            <span title="{{ $d['nombre'] }}">{{ $d['icono'] }}</span>
        @endforeach
    </span>
@endif
