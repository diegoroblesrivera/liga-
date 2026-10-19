@php
    $ligaActual = \App\Support\LigaActual::liga();
    $misLigas = $ligaActual
        ? \App\Models\Liga::whereIn('id', auth()->user()->ligasIds())->orderBy('nombre')->get()
        : collect();
@endphp
<nav x-data="{ open: false }" class="bg-balon">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" aria-label="{{ config('app.name') }}">
                        <x-logo class="text-xl [&>span:last-child]:hidden lg:[&>span:last-child]:inline" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-6 sm:-my-px sm:ms-8 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        Inicio
                    </x-nav-link>

                    {{-- Campeonato: solo para quien participa en un torneo --}}
                    @if (auth()->user()?->participaEnTorneos())
                    <x-nav-link :href="route('equipos.index')" :active="request()->routeIs('equipos.*')">
                        {{ __('Equipos') }}
                    </x-nav-link>
                    <x-nav-link :href="route('tablas.posiciones')" :active="request()->routeIs('tablas.posiciones')">
                        {{ __('Posiciones') }}
                    </x-nav-link>
                    <x-nav-link :href="route('tablas.goleadores')" :active="request()->routeIs('tablas.goleadores')">
                        {{ __('Goleadores') }}
                    </x-nav-link>
                    @if (auth()->user()?->hasRole('admin'))
                    <x-nav-link :href="route('arbitros.index')" :active="request()->routeIs('arbitros.*')">
                        {{ __('Árbitros') }}
                    </x-nav-link>
                    <x-nav-link :href="route('fixtures.create')" :active="request()->routeIs('fixtures.*')">
                        {{ __('Fixture') }}
                    </x-nav-link>
                    <x-nav-link :href="route('configuracion.index')" :active="request()->routeIs('configuracion.*', 'jugadores.index')">
                        {{ __('Configuración') }}
                    </x-nav-link>

                    @endif
                    <x-nav-link :href="route('partidos.index')" :active="request()->routeIs('partidos.*')">
                        {{ __('Partidos') }}
                    </x-nav-link>

                    @if (Gate::allows('gestionar-pagos'))
                        <x-nav-link :href="route('pagos.panel')" :active="request()->routeIs('pagos.panel')">
                            {{ __('Pagos') }}
                        </x-nav-link>
                    @elseif (auth()->user()?->equipo_id)
                        <x-nav-link :href="route('pagos.mis-pagos')" :active="request()->routeIs('pagos.mis-pagos')">
                            {{ __('Pagos') }}
                        </x-nav-link>
                    @endif
                    @endif

                    <x-nav-link :href="route('pichangas.index')" :active="request()->routeIs('pichangas.*')">
                        {{ __('Pichangas') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                {{-- Campeonato actual y cambio entre los que participa --}}
                @if ($ligaActual)
                    <x-dropdown align="right" width="w-64">
                        <x-slot name="trigger">
                            <button class="inline-flex max-w-[14rem] items-center rounded-md px-3 py-2 text-sm font-semibold leading-4 text-cancha-300 hover:bg-white/10 hover:text-cancha-200 focus:outline-none">
                                <span class="truncate">🏆 {{ $ligaActual->nombre }}</span>
                                <svg class="ms-1 h-4 w-4 shrink-0 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <div class="px-4 py-2 text-xs uppercase tracking-wide text-gray-400">Tus campeonatos</div>
                            @foreach ($misLigas as $l)
                                <form method="POST" action="{{ route('campeonatos.usar', $l) }}">
                                    @csrf
                                    <button type="submit" class="block w-full px-4 py-2 text-start text-sm leading-5 hover:bg-gray-100 {{ $l->id === $ligaActual->id ? 'font-semibold text-indigo-700' : 'text-gray-700' }}">
                                        {{ $l->id === $ligaActual->id ? '✓ ' : '' }}{{ $l->nombre }}
                                    </button>
                                </form>
                            @endforeach
                            <div class="border-t border-gray-100"></div>
                            <x-dropdown-link :href="route('campeonatos.create')">+ Crear campeonato</x-dropdown-link>
                        </x-slot>
                    </x-dropdown>
                @endif

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-300 hover:text-white hover:bg-white/10 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>
                        <x-dropdown-link :href="route('logros.mios')">🏅 Mis logros</x-dropdown-link>

                        @if (auth()->user()->is_super_admin)
                            <x-dropdown-link :href="route('admin.campeonatos')">Panel TuTorneo</x-dropdown-link>
                        @endif

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-300 hover:text-white hover:bg-white/10 focus:outline-none focus:bg-white/10 focus:text-white transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                Inicio
            </x-responsive-nav-link>

{{-- Campeonato: solo para quien participa en un torneo --}}
@if (auth()->user()?->participaEnTorneos())
            <x-responsive-nav-link :href="route('equipos.index')" :active="request()->routeIs('equipos.*')">
    {{ __('Equipos') }}
</x-responsive-nav-link>

<x-responsive-nav-link :href="route('partidos.index')" :active="request()->routeIs('partidos.*')">
    {{ __('Fixture') }}
</x-responsive-nav-link>

<x-responsive-nav-link :href="route('tablas.posiciones')" :active="request()->routeIs('tablas.posiciones')">
    {{ __('Posiciones') }}
</x-responsive-nav-link>

<x-responsive-nav-link :href="route('tablas.goleadores')" :active="request()->routeIs('tablas.goleadores')">
    {{ __('Goleadores') }}
</x-responsive-nav-link>

@if (auth()->user()?->hasRole('admin'))
    <x-responsive-nav-link :href="route('arbitros.index')" :active="request()->routeIs('arbitros.*')">
        {{ __('Árbitros') }}
    </x-responsive-nav-link>
    <x-responsive-nav-link :href="route('configuracion.index')" :active="request()->routeIs('configuracion.*', 'jugadores.index')">
        {{ __('Configuración') }}
    </x-responsive-nav-link>
@endif

@if (Gate::allows('gestionar-pagos'))
    <x-responsive-nav-link :href="route('pagos.panel')" :active="request()->routeIs('pagos.panel')">
        {{ __('Pagos') }}
    </x-responsive-nav-link>
@elseif (auth()->user()?->equipo_id)
    <x-responsive-nav-link :href="route('pagos.mis-pagos')" :active="request()->routeIs('pagos.mis-pagos')">
        {{ __('Pagos') }}
    </x-responsive-nav-link>
@endif
@endif

<x-responsive-nav-link :href="route('pichangas.index')" :active="request()->routeIs('pichangas.*')">
    {{ __('Pichangas') }}
</x-responsive-nav-link>

        </div>

        {{-- Campeonato actual y cambio entre los que participa --}}
        @if ($ligaActual)
            <div class="border-t border-white/10 pt-3 pb-2">
                <div class="px-4 text-xs uppercase tracking-wide text-gray-400">Campeonato</div>
                @foreach ($misLigas as $l)
                    <form method="POST" action="{{ route('campeonatos.usar', $l) }}">
                        @csrf
                        <button type="submit" class="block w-full border-l-4 py-2 pe-4 ps-3 text-start text-base {{ $l->id === $ligaActual->id ? 'border-cancha-400 bg-white/10 font-semibold text-white' : 'border-transparent text-gray-300 hover:bg-white/5 hover:text-white' }}">
                            🏆 {{ $l->nombre }}
                        </button>
                    </form>
                @endforeach
                <x-responsive-nav-link :href="route('campeonatos.create')">+ Crear campeonato</x-responsive-nav-link>
            </div>
        @endif

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-white/10">
            <div class="px-4">
                <div class="font-medium text-base text-white">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-400">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('logros.mios')">🏅 Mis logros</x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>

    {{-- Línea de césped bajo el menú --}}
    <div class="franjas-cesped h-1.5"></div>
</nav>
