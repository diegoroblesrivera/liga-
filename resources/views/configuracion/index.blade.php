<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Configuración</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

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
