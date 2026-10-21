{{-- Menú lateral (pantallas grandes) para quien tiene muchas opciones: hoy, el super admin. --}}
@php
    $yo = auth()->user();
    $ligaActual = \App\Support\LigaActual::liga();
    $misLigas = $ligaActual ? \App\Models\Liga::whereIn('id', $yo->ligasIds())->orderBy('nombre')->get() : collect();
    $enTorneos = $yo->participaEnTorneos();
    $esAdmin = $yo->hasRole('admin');

    $secciones = array_filter([
        'Campeonato' => $enTorneos ? array_filter([
            ['Inicio', 'dashboard', ['dashboard'], '🏠'],
            ['Partidos', 'partidos.index', ['partidos.*'], '📅'],
            ['Posiciones', 'tablas.posiciones', ['tablas.posiciones'], '📊'],
            ['Goleadores', 'tablas.goleadores', ['tablas.goleadores'], '⚽'],
            ['Equipos', 'equipos.index', ['equipos.*', 'carga.*'], '👕'],
            $esAdmin ? ['Árbitros', 'arbitros.index', ['arbitros.*'], '🟨'] : null,
            \Illuminate\Support\Facades\Gate::allows('gestionar-pagos') ? ['Pagos', 'pagos.panel', ['pagos.*'], '💵'] : null,
            $esAdmin ? ['Crear torneo', 'fixtures.create', ['fixtures.*'], '🏆'] : null,
            $esAdmin ? ['Configuración', 'configuracion.index', ['configuracion.*', 'campeonatos.edit', 'campeonatos.plan', 'jugadores.index', 'torneos.*', 'canchas.*'], '⚙️'] : null,
        ]) : [['Inicio', 'dashboard', ['dashboard'], '🏠']],
        'Pichangas' => [
            ['Mis pichangas', 'pichangas.index', ['pichangas.*'], '🔥'],
        ],
        'Panel TuTorneo' => $yo->is_super_admin ? [
            ['Métricas', 'admin.metricas', ['admin.metricas'], '📈'],
            ['Campeonatos y Pro', 'admin.campeonatos', ['admin.campeonatos'], '⭐'],
        ] : [],
        'Mi cuenta' => [
            ['Mis logros', 'logros.mios', ['logros.*'], '🏅'],
            ['Perfil', 'profile.edit', ['profile.*'], '👤'],
        ],
    ]);
@endphp

<aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col bg-balon text-gray-300 lg:flex">
    <div class="flex h-14 shrink-0 items-center px-5">
        <a href="{{ route('dashboard') }}" aria-label="{{ config('app.name') }}"><x-logo class="text-xl" /></a>
    </div>

    {{-- Campeonato actual --}}
    @if ($ligaActual)
        <div class="px-3 pb-3" x-data="{ abierto: false }">
            <button type="button" @click="abierto = ! abierto"
                    class="flex w-full items-center justify-between rounded-md bg-white/5 px-3 py-2 text-left text-sm font-semibold text-cancha-300 hover:bg-white/10">
                <span class="truncate">🏆 {{ $ligaActual->nombre }}</span>
                <svg class="h-4 w-4 shrink-0 transition" :class="abierto && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.3 7.3a1 1 0 011.4 0L10 10.6l3.3-3.3a1 1 0 111.4 1.4l-4 4a1 1 0 01-1.4 0l-4-4a1 1 0 010-1.4z" clip-rule="evenodd" /></svg>
            </button>
            <div x-show="abierto" x-cloak class="mt-1 max-h-64 overflow-y-auto rounded-md bg-white/5 py-1">
                @foreach ($misLigas as $l)
                    <form method="POST" action="{{ route('campeonatos.usar', $l) }}">
                        @csrf
                        <button type="submit" class="block w-full truncate px-3 py-1.5 text-left text-sm {{ $l->id === $ligaActual->id ? 'font-semibold text-white' : 'text-gray-400 hover:text-white' }}">
                            {{ $l->id === $ligaActual->id ? '✓ ' : '' }}{{ $l->nombre }}
                        </button>
                    </form>
                @endforeach
                <a href="{{ route('campeonatos.create') }}" class="block border-t border-white/10 px-3 py-1.5 text-sm text-gray-400 hover:text-white">+ Crear campeonato</a>
            </div>
        </div>
    @endif

    <nav class="flex-1 space-y-3 overflow-y-auto px-3 pb-3">
        @foreach ($secciones as $titulo => $items)
            <div>
                <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-500">{{ $titulo }}</p>
                @foreach ($items as [$texto, $ruta, $activa, $icono])
                    @php
                        $activo = request()->routeIs(...$activa);
                    @endphp
                    <a href="{{ route($ruta) }}"
                       class="flex items-center gap-3 rounded-md px-3 py-1.5 text-sm {{ $activo ? 'bg-white/10 font-semibold text-white' : 'text-gray-300 hover:bg-white/5 hover:text-white' }}">
                        <span class="w-5 text-center">{{ $icono }}</span>{{ $texto }}
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    <div class="border-t border-white/10 p-4">
        <p class="truncate text-sm font-medium text-white">{{ $yo->name }}</p>
        <p class="truncate text-xs text-gray-500">{{ $yo->email }}</p>
        <form method="POST" action="{{ route('logout') }}" class="mt-2">
            @csrf
            <button type="submit" class="text-xs text-gray-400 hover:text-white">Cerrar sesión</button>
        </form>
    </div>

    <div class="franjas-cesped h-1.5"></div>
</aside>
