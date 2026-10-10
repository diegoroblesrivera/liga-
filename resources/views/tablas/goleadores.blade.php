<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Goleadores</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">

            @include('tablas._selector')

            @if (! $torneo)
                <div class="bg-white p-6 text-sm text-gray-500 shadow-sm sm:rounded-lg">
                    No hay torneos todavía.
                </div>
            @else
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3 text-left">#</th>
                                <th class="px-4 py-3 text-left">Jugador</th>
                                <th class="px-4 py-3 text-left">Equipo</th>
                                <th class="px-3 py-3 text-center font-semibold text-gray-700">Goles</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($goleadores as $i => $fila)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-500">{{ $i + 1 }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $fila->jugador }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $fila->equipo }}</td>
                                    <td class="px-3 py-3 text-center font-semibold text-gray-900">{{ $fila->goles }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                        Todavía no hay goles registrados en este torneo.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <p class="mt-3 text-xs text-gray-400">Los autogoles no cuentan para el goleador.</p>
            @endif

        </div>
    </div>
</x-app-layout>
