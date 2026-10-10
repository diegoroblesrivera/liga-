<x-publico-layout>
    {{-- Qué es la app --}}
    <section class="rounded-2xl bg-gradient-to-br from-indigo-600 to-indigo-800 px-6 py-12 text-center text-white shadow-sm sm:px-12">
        <h1 class="text-3xl font-bold sm:text-4xl">Organiza tu fútbol sin planillas ni grupos eternos</h1>
        <p class="mx-auto mt-4 max-w-2xl text-indigo-100">
            Arma la pichanga de cada semana o administra un campeonato completo: quién va, cuánto paga cada uno,
            equipos, fixture, resultados, tabla de posiciones y goleadores. Todo en un solo lugar y desde el celular.
        </p>

        <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <a href="{{ route('pichangas.create') }}"
               class="w-full rounded-lg bg-white px-6 py-3 text-base font-semibold text-indigo-700 shadow hover:bg-indigo-50 sm:w-auto">
                ⚽ Crea una pichanga
            </a>
            <a href="{{ route('campeonatos.create') }}"
               class="w-full rounded-lg border-2 border-white px-6 py-3 text-base font-semibold text-white hover:bg-white/10 sm:w-auto">
                🏆 Crea un campeonato
            </a>
        </div>
        @guest
            <p class="mt-4 text-sm text-indigo-200">Gratis. Entra con tu cuenta de Google o con tu correo.</p>
        @endguest
    </section>

    {{-- Qué hace --}}
    <section class="mt-10 grid gap-6 sm:grid-cols-2">
        <div class="rounded-lg bg-white p-6 shadow-sm">
            <div class="text-3xl">⚽</div>
            <h2 class="mt-2 text-lg font-semibold text-gray-900">Pichangas semanales</h2>
            <ul class="mt-3 space-y-2 text-sm text-gray-600">
                <li>✓ Comparte un link y tus amigos se suman al grupo.</li>
                <li>✓ Cada semana marcan <strong>Voy / No voy</strong>; con cupo y lista de espera.</li>
                <li>✓ El valor de la cancha se divide solo entre los que van.</li>
                <li>✓ Cada uno sube su comprobante de transferencia.</li>
                <li>✓ Sorteo de equipos, resultado, goleadores y lista para la portería.</li>
            </ul>
        </div>
        <div class="rounded-lg bg-white p-6 shadow-sm">
            <div class="text-3xl">🏆</div>
            <h2 class="mt-2 text-lg font-semibold text-gray-900">Campeonatos</h2>
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
        <h2 class="text-xl font-semibold text-gray-900">Campeonatos</h2>
        <p class="mt-1 text-sm text-gray-500">Sigue la tabla, las fechas y los goleadores de los campeonatos abiertos al público.</p>

        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($campeonatos as $c)
                <a href="{{ route('campeonatos.publico', $c) }}"
                   class="group rounded-lg bg-white p-5 shadow-sm transition hover:shadow-md">
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
