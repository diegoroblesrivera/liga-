<x-publico-layout>
    {{-- Qué es la app --}}
    <section class="franjas-cesped rounded-2xl px-4 py-10 shadow-sm sm:px-10 sm:py-14">
        <div class="mx-auto max-w-3xl rounded-2xl border-4 border-white bg-balon px-6 py-10 text-center shadow-xl sm:px-12">
            <h1 class="titulo-deportivo text-3xl text-white sm:text-5xl">Tu fútbol, organizado</h1>
            <p class="marcador mt-3 text-4xl text-cancha-400 sm:text-5xl">Pichangas · Campeonatos</p>
            <p class="mx-auto mt-5 max-w-2xl text-gray-200">
                Arma la pichanga de cada semana o administra un campeonato completo: quién va, cuánto paga cada uno,
                equipos, fixture, resultados, tabla de posiciones y goleadores. Todo en un solo lugar y desde el celular.
            </p>

            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ route('pichangas.create') }}"
                   class="w-full rounded-lg bg-cancha-400 px-6 py-3 text-base font-bold text-balon shadow hover:bg-cancha-300 sm:w-auto">
                    ⚽ Crea una pichanga
                </a>
                <a href="{{ route('campeonatos.create') }}"
                   class="w-full rounded-lg border-2 border-white px-6 py-3 text-base font-bold text-white hover:bg-white/10 sm:w-auto">
                    🏆 Crea un campeonato
                </a>
            </div>
            @guest
                <p class="mt-4 text-sm text-gray-400">Gratis. Entra con tu cuenta de Google o con tu correo.</p>
            @endguest
        </div>
    </section>

    {{-- Qué hace --}}
    <section class="mt-10 grid gap-6 sm:grid-cols-2">
        <div class="rounded-lg border-t-4 border-cancha-400 bg-white p-6 shadow-sm">
            <div class="text-3xl">⚽</div>
            <h2 class="titulo-deportivo mt-2 text-xl text-balon">Pichangas semanales</h2>
            <ul class="mt-3 space-y-2 text-sm text-gray-600">
                <li>✓ Comparte un link y tus amigos se suman al grupo.</li>
                <li>✓ Cada semana marcan <strong>Voy / No voy</strong>; con cupo y lista de espera.</li>
                <li>✓ El valor de la cancha se divide solo entre los que van.</li>
                <li>✓ Cada uno sube su comprobante de transferencia.</li>
                <li>✓ Sorteo de equipos, resultado, goleadores y lista para la portería.</li>
            </ul>
        </div>
        <div class="rounded-lg border-t-4 border-cancha-400 bg-white p-6 shadow-sm">
            <div class="text-3xl">🏆</div>
            <h2 class="titulo-deportivo mt-2 text-xl text-balon">Campeonatos</h2>
            <ul class="mt-3 space-y-2 text-sm text-gray-600">
                <li>✓ Equipos con su DT, que inscribe a sus jugadores por link.</li>
                <li>✓ Fixture automático todos contra todos, ida y vuelta.</li>
                <li>✓ Árbitros que cargan goles y tarjetas desde la cancha.</li>
                <li>✓ Tabla de posiciones y goleadores al día.</li>
                <li>✓ Control de pagos por partido con comprobantes.</li>
            </ul>
        </div>
    </section>

    {{-- Campeonatos públicos --}}
    <section class="mt-12">
        <h2 class="titulo-deportivo text-2xl text-balon">Campeonatos</h2>
        <p class="mt-1 text-sm text-gray-500">Sigue la tabla, las fechas y los goleadores de los campeonatos abiertos al público.</p>

        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($campeonatos as $c)
                <a href="{{ route('campeonatos.publico', $c) }}"
                   class="group rounded-lg border-t-4 border-cancha-400 bg-white p-5 shadow-sm transition hover:shadow-md">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="font-semibold text-gray-900 group-hover:text-indigo-700">{{ $c->nombre }}</h3>
                        <span class="text-xl">🏆</span>
                    </div>
                    @if ($c->descripcion)
                        <p class="mt-1 line-clamp-2 text-sm text-gray-600">{{ $c->descripcion }}</p>
                    @endif
                    <p class="mt-3 text-xs text-gray-400">
                        {{ $c->equipos_count }} {{ $c->equipos_count === 1 ? 'equipo' : 'equipos' }}
                        · {{ $c->torneos_count }} {{ $c->torneos_count === 1 ? 'torneo' : 'torneos' }}
                    </p>
                    <p class="mt-3 text-sm font-medium text-indigo-600">Ver posiciones y fechas →</p>
                </a>
            @empty
                <p class="text-sm text-gray-500">Todavía no hay campeonatos públicos.</p>
            @endforelse
        </div>
    </section>
</x-publico-layout>
