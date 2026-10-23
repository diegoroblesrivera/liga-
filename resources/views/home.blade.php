<x-publico-layout>
    {{-- Qué es la app --}}
    <section class="relative overflow-hidden rounded-2xl bg-balon bg-[url('/videos/portada.jpg')] bg-cover bg-center shadow-sm">
        {{-- Video de fondo: toma aérea de canchas al amanecer (Pixabay, licencia libre sin atribución).
             Mudo y en bucle de ida y vuelta; con "reducir movimiento" queda la imagen fija. --}}
        <video class="video-portada absolute inset-0 h-full w-full object-cover"
               autoplay muted loop playsinline preload="auto" aria-hidden="true"
               poster="{{ asset('videos/portada.jpg') }}">
            <source src="{{ asset('videos/portada.mp4') }}" type="video/mp4">
        </video>
        <div class="absolute inset-0 bg-gradient-to-b from-balon/50 via-balon/55 to-balon/75"></div>

        <div class="relative px-4 py-12 sm:px-10 sm:py-20">
        <div class="texto-sobre-video mx-auto max-w-3xl px-2 text-center sm:px-6">
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
                <p class="mt-4 text-sm text-gray-300">Gratis. Entra con tu cuenta de Google o con tu correo.</p>
            @endguest
        </div>
        </div>
    </section>

    {{-- Dos caminos: pichangas y campeonatos --}}
    <section class="mt-10 grid gap-6 sm:grid-cols-2">
        <div class="rounded-lg border-t-4 border-cancha-400 bg-white p-6 shadow-sm">
            <div class="text-3xl">⚽</div>
            <h2 class="titulo-deportivo mt-2 text-xl text-balon">Pichangas semanales</h2>
            <ul class="mt-3 space-y-2 text-sm text-gray-600">
                <li>✓ Comparte el link por <strong>WhatsApp</strong> y tus amigos se suman al grupo.</li>
                <li>✓ Cada semana marcan <strong>Voy / No voy</strong>; con cupo y lista de espera.</li>
                <li>✓ Cobra como prefieras: <strong>dividir la cancha</strong> entre los que van o una <strong>cuota fija</strong>.</li>
                <li>✓ Cada uno sube su comprobante de transferencia.</li>
                <li>✓ Sorteo de equipos, resultado, goleadores y lista para la portería.</li>
            </ul>
        </div>
        <div class="rounded-lg border-t-4 border-cancha-400 bg-white p-6 shadow-sm">
            <div class="text-3xl">🏆</div>
            <h2 class="titulo-deportivo mt-2 text-xl text-balon">Campeonatos</h2>
            <ul class="mt-3 space-y-2 text-sm text-gray-600">
                <li>✓ <strong>Todos contra todos, grupos o eliminación directa</strong>, en una o dos etapas.</li>
                <li>✓ Carga equipos y planteles <strong>desde un Excel</strong>; cada uno recibe su invitación.</li>
                <li>✓ Árbitros que cargan goles y tarjetas desde la cancha.</li>
                <li>✓ Tabla, goleadores, llaves y suspensiones al día.</li>
                <li>✓ Lista de asistencia con <strong>QR</strong> y control de pagos con comprobantes.</li>
            </ul>
        </div>
    </section>

    {{-- Todo lo que incluye --}}
    @php
        $funciones = [
            ['🏟️', 'Formatos de torneo', 'Todos contra todos (ida o ida y vuelta), fase de grupos con play offs o eliminación directa de 8 o 16. Los cruces se sortean solos.'],
            ['📱', 'Lista con QR', 'Cada fecha tiene un QR: el jugador lo escanea en la cancha y queda presente. Opcional: sin lista no se juega.'],
            ['📥', 'Carga por Excel', 'Sube todos los equipos con su DT, o el plantel completo, de una vez. A cada uno le llega su invitación por correo.'],
            ['💬', 'Invitaciones por WhatsApp', 'Links para DT, jugadores y árbitros con el mensaje listo para mandar al grupo.'],
            ['📊', 'Tablas en vivo', 'Posiciones, tablas por grupo, llaves y goleadores al instante. Con página pública para compartir.'],
            ['🟨', 'Árbitro en la cancha', 'Goles, tarjetas y penales desde el celular. Las suspensiones por tarjetas se calculan solas.'],
            ['🏅', 'Logros e insignias', 'Primer gol, hat-trick, valla invicta, campeón... los jugadores coleccionan insignias en su perfil.'],
            ['💵', 'Pagos con comprobante', 'Cuánto paga cada uno, quién pagó y los comprobantes guardados en privado.'],
            ['📲', 'Se instala como app', 'Agrégala a la pantalla de inicio del celular; funciona en Android y iPhone.'],
        ];
    @endphp
    <section id="funciones" class="mt-14 scroll-mt-6">
        <p class="text-center text-xs font-semibold uppercase tracking-widest text-cancha-600">Funciones</p>
        <h2 class="titulo-deportivo mt-1 text-center text-2xl text-balon sm:text-3xl">Todo lo que necesita tu liga</h2>
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($funciones as [$icono, $titulo, $texto])
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-cancha-50 text-2xl">{{ $icono }}</div>
                    <h3 class="mt-3 font-semibold text-gray-900">{{ $titulo }}</h3>
                    <p class="mt-1 text-sm text-gray-600">{{ $texto }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Cómo funciona --}}
    <section id="como-funciona" class="mt-14 scroll-mt-6 rounded-2xl bg-balon px-6 py-10 text-white sm:px-10">
        <p class="text-center text-xs font-semibold uppercase tracking-widest text-cancha-300">Cómo funciona</p>
        <h2 class="titulo-deportivo mt-1 text-center text-2xl sm:text-3xl">Tu campeonato listo en 3 pasos</h2>
        <ol class="mt-8 grid gap-6 sm:grid-cols-3">
            @foreach ([
                ['Crea tu campeonato', 'Ponle nombre y elige el formato: liga, grupos o llaves. Es gratis.'],
                ['Carga los equipos', 'Uno a uno o todos desde un Excel. Cada DT recibe su invitación y suma a sus jugadores.'],
                ['Comparte y a jugar', 'Manda los links por WhatsApp. Los árbitros cargan los resultados y la tabla se actualiza sola.'],
            ] as $i => [$titulo, $texto])
                <li class="text-center">
                    <span class="marcador mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-cancha-400 text-3xl text-balon">{{ $i + 1 }}</span>
                    <h3 class="mt-3 font-semibold">{{ $titulo }}</h3>
                    <p class="mt-1 text-sm text-gray-300">{{ $texto }}</p>
                </li>
            @endforeach
        </ol>
        <div class="mt-8 text-center">
            <a href="{{ route('campeonatos.create') }}" class="inline-block rounded-lg bg-cancha-400 px-6 py-3 font-bold text-balon hover:bg-cancha-300">🏆 Crear mi campeonato</a>
        </div>
    </section>

    {{-- Logros --}}
    <section class="mt-14 grid items-center gap-8 lg:grid-cols-2">
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-cancha-600">Logros</p>
            <h2 class="titulo-deportivo mt-1 text-2xl text-balon sm:text-3xl">Cada gol cuenta</h2>
            <p class="mt-3 text-gray-600">
                Los jugadores ganan insignias con lo que pasa en la cancha: su primer gol, un hat-trick, 50 partidos,
                una valla invicta. Las ven en su perfil, junto a su nombre en la tabla de goleadores, y reciben un aviso
                cada vez que desbloquean una.
            </p>
            <p class="mt-3 text-sm text-gray-500">En los campeonatos <strong>Pro</strong> se suman Campeón, Pichichi, Racha goleadora, Figura de la fecha y Asistencia perfecta.</p>
        </div>
        <div class="grid grid-cols-3 gap-3 sm:grid-cols-4">
            @foreach (['primer_gol', 'hat_trick', 'goleador_10', 'valla_invicta', 'fair_play', 'partidos_50', 'campeon', 'pichichi'] as $clave)
                @php
                    $l = \App\Support\Logros\Catalogo::get($clave);
                @endphp
                <div class="relative flex flex-col items-center rounded-lg border border-cancha-200 bg-white p-3 text-center shadow-sm">
                    @if ($l['pro'])
                        <span class="absolute right-1.5 top-1 text-[10px]" title="Logro Pro">⭐</span>
                    @endif
                    <span class="text-3xl">{{ $l['icono'] }}</span>
                    <span class="mt-1 text-xs font-semibold text-gray-800">{{ $l['nombre'] }}</span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Planes --}}
    <section id="planes" class="mt-14 scroll-mt-6">
        <p class="text-center text-xs font-semibold uppercase tracking-widest text-cancha-600">Planes</p>
        <h2 class="titulo-deportivo mt-1 text-center text-2xl text-balon sm:text-3xl">Empieza gratis</h2>
        <div class="mx-auto mt-6 grid max-w-3xl gap-4 sm:grid-cols-2">
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-gray-900">Gratis</h3>
                <p class="marcador mt-1 text-4xl text-balon">$0</p>
                <p class="mt-2 text-sm text-gray-600">Pichangas sin límite y campeonatos de hasta <strong>{{ $limite }} equipos</strong>, con todas las funciones.</p>
            </div>
            <div class="rounded-lg bg-white p-6 shadow-sm ring-2 ring-cancha-400">
                <h3 class="font-semibold text-gray-900">Pro <span class="text-xs font-normal text-gray-500">· por campeonato</span></h3>
                <p class="marcador mt-1 text-4xl text-balon">${{ number_format($precio, 0, ',', '.') }}<span class="text-base text-gray-500"> / año</span></p>
                <p class="mt-2 text-sm text-gray-600"><strong>Equipos ilimitados</strong> y los logros Pro. Solo paga el organizador; los jugadores usan su cuenta gratis.</p>
            </div>
        </div>
        <p class="mt-4 text-center"><a href="{{ route('precios') }}" class="font-medium text-indigo-600 hover:text-indigo-800">Ver planes y preguntas frecuentes →</a></p>
    </section>

    {{-- Campeonatos públicos --}}
    <section id="campeonatos" class="mt-14 scroll-mt-6">
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

    {{-- Preguntas frecuentes + llamado final --}}
    <section class="mt-14 grid gap-8 lg:grid-cols-2">
        <div>
            <h2 class="titulo-deportivo text-2xl text-balon">Preguntas frecuentes</h2>
            <div class="mt-4 divide-y divide-gray-200 rounded-lg bg-white shadow-sm">
                @foreach ([
                    ['¿Los jugadores tienen que pagar?', 'No. Las cuentas de jugadores, DT y árbitros son gratis. El plan Pro lo paga solo el organizador del campeonato.'],
                    ['¿Necesito instalar algo?', 'No. Funciona desde el navegador del celular o del computador y, si quieres, la agregas a la pantalla de inicio como una app.'],
                    ['¿Puedo cargar los equipos que ya tengo en una planilla?', 'Sí. Descarga la plantilla de Excel, pega tus equipos con su DT (o tu plantel) y súbela. A cada uno le llega su invitación.'],
                    ['¿Qué pasa si un partido de eliminación termina empatado?', 'El árbitro anota los penales al finalizar y la siguiente ronda se arma sola.'],
                ] as [$pregunta, $respuesta])
                    <details class="group p-4">
                        <summary class="flex cursor-pointer list-none items-center justify-between font-medium text-gray-900">
                            {{ $pregunta }}
                            <span class="ms-3 text-gray-400 transition group-open:rotate-45">＋</span>
                        </summary>
                        <p class="mt-2 text-sm text-gray-600">{{ $respuesta }}</p>
                    </details>
                @endforeach
            </div>
        </div>
        <div class="flex flex-col justify-center rounded-2xl bg-cancha-600 p-8 text-center text-white">
            <h2 class="titulo-deportivo text-2xl sm:text-3xl">¿Listos para el pitazo inicial?</h2>
            <p class="mt-2 text-cancha-100">Crea tu pichanga o tu campeonato en un par de minutos.</p>
            <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ route('pichangas.create') }}" class="rounded-lg bg-white px-5 py-3 font-bold text-cancha-700 hover:bg-cancha-50">⚽ Crear pichanga</a>
                <a href="{{ route('campeonatos.create') }}" class="rounded-lg border-2 border-white px-5 py-3 font-bold text-white hover:bg-white/10">🏆 Crear campeonato</a>
            </div>
        </div>
    </section>
</x-publico-layout>
