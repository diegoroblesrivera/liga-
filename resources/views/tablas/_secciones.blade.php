{{-- Posiciones según el formato del torneo. Recibe: $secciones (Torneos\Vista::secciones) --}}
@foreach ($secciones as $s)
    <section>
        @if ($s['titulo'])
            <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-gray-500">{{ $s['titulo'] }}</h3>
        @endif

        @if ($s['tipo'] === 'tabla')
            @include('tablas._tabla', ['filas' => $s['filas'], 'clasifican' => $s['clasifican']])
            @if ($s['clasifican'])
                <p class="mt-1 text-xs text-gray-500"><span class="inline-block h-2.5 w-2.5 rounded-sm align-middle" style="background:#B0DFBA"></span> Los {{ $s['clasifican'] }} primeros pasan a la eliminación directa.</p>
            @endif
        @elseif ($s['tipo'] === 'grupos')
            <div class="grid gap-4 lg:grid-cols-2">
                @foreach ($s['grupos'] as $letra => $filas)
                    <div>
                        <p class="mb-1 text-sm font-semibold text-gray-800">Grupo {{ $letra }}</p>
                        @include('tablas._tabla', ['filas' => $filas, 'clasifican' => $s['clasifican']])
                    </div>
                @endforeach
            </div>
            <p class="mt-1 text-xs text-gray-500"><span class="inline-block h-2.5 w-2.5 rounded-sm align-middle" style="background:#B0DFBA"></span> Clasifican {{ $s['clasifican'] === 1 ? 'el primero' : 'los '.$s['clasifican'].' primeros' }} de cada grupo.</p>
        @else
            @include('tablas._llave', ['llave' => $s])
        @endif
    </section>
@endforeach
