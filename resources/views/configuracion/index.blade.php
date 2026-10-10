<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Configuración</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                <a href="{{ route('campeonatos.edit') }}"
                   class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-indigo-100 hover:shadow-md transition-shadow sm:col-span-2">
                    <h3 class="text-sm font-semibold text-gray-900">🏆 Datos del campeonato</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ \App\Support\LigaActual::liga()?->nombre }} ·
                        {{ \App\Support\LigaActual::liga()?->publica ? 'Público: aparece en la portada' : 'Privado' }}.
                        Cambia el nombre, la descripción y si es público.
                    </p>
                </a>

                <a href="{{ route('jugadores.index') }}"
                   class="rounded-lg bg-white p-5 shadow-sm hover:shadow-md transition-shadow">
                    <h3 class="text-sm font-semibold text-gray-900">Jugadores</h3>
                    <p class="mt-1 text-sm text-gray-500">Buscar cualquier jugador, editar sus datos y su foto.</p>
                </a>

                <a href="{{ route('equipos.index') }}"
                   class="rounded-lg bg-white p-5 shadow-sm hover:shadow-md transition-shadow">
                    <h3 class="text-sm font-semibold text-gray-900">Equipos</h3>
                    <p class="mt-1 text-sm text-gray-500">Crear equipos, editar datos y generar invitaciones.</p>
                </a>

                <a href="{{ route('arbitros.index') }}"
                   class="rounded-lg bg-white p-5 shadow-sm hover:shadow-md transition-shadow">
                    <h3 class="text-sm font-semibold text-gray-900">Árbitros</h3>
                    <p class="mt-1 text-sm text-gray-500">Ver árbitros registrados y generar links de invitación.</p>
                </a>

                <a href="{{ route('fixtures.create') }}"
                   class="rounded-lg bg-white p-5 shadow-sm hover:shadow-md transition-shadow">
                    <h3 class="text-sm font-semibold text-gray-900">Fixture</h3>
                    <p class="mt-1 text-sm text-gray-500">Generar el fixture automático del torneo.</p>
                </a>

                <a href="{{ route('torneos.index') }}"
                   class="rounded-lg bg-white p-5 shadow-sm hover:shadow-md transition-shadow">
                    <h3 class="text-sm font-semibold text-gray-900">Torneos</h3>
                    <p class="mt-1 text-sm text-gray-500">Crear torneos y editar sus fechas y temporada.</p>
                </a>

                <a href="{{ route('canchas.index') }}"
                   class="rounded-lg bg-white p-5 shadow-sm hover:shadow-md transition-shadow">
                    <h3 class="text-sm font-semibold text-gray-900">Canchas</h3>
                    <p class="mt-1 text-sm text-gray-500">Administrar canchas y direcciones.</p>
                </a>

            </div>

        </div>
    </div>
</x-app-layout>
