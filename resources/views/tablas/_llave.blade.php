{{-- Llave de eliminación directa. Recibe: $llave con rondas, tercer y pendiente --}}
@if ($llave['pendiente'])
    <div class="rounded-lg bg-white p-5 text-sm text-gray-500 shadow-sm">
        La llave se arma cuando termine la etapa anterior, con los clasificados cruzados por ubicación.
    </div>
@else
    <div class="overflow-x-auto rounded-lg bg-white p-4 shadow-sm">
        <div class="flex min-w-max gap-4">
            @foreach ($llave['rondas'] as $ronda)
                <div class="flex w-48 flex-col">
                    <p class="mb-2 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $ronda['nombre'] }}</p>
                    <div class="flex flex-1 flex-col justify-around gap-3">
                        @foreach ($ronda['partidos'] as $p)
                            @include('tablas._cruce', ['p' => $p])
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        @if ($llave['tercer'])
            <div class="mt-4 w-48">
                <p class="mb-2 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Tercer lugar</p>
                @include('tablas._cruce', ['p' => $llave['tercer']])
            </div>
        @endif
    </div>
@endif
