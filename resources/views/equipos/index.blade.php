<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Equipos</h2>

            @can('create', App\Models\Equipo::class)
                @php($ligaPlan = \App\Support\LigaActual::liga())
                <div class="flex items-center gap-3">
                    @unless ($ligaPlan->esPro())
                        <a href="{{ route('campeonatos.plan') }}" class="text-xs text-gray-500 hover:text-gray-700"
                           title="Plan Gratis: hasta {{ \App\Models\Liga::EQUIPOS_PLAN_GRATIS }} equipos">
                            {{ $equipos->count() }}/{{ \App\Models\Liga::EQUIPOS_PLAN_GRATIS }} equipos · Gratis
                        </a>
                    @endunless
                    <a href="{{ route('equipos.create') }}"
                       class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        Crear equipo
                    </a>
                </div>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                @forelse ($equipos as $equipo)
                    <a href="{{ route('equipos.show', $equipo) }}"
                       class="flex items-center justify-between border-b border-gray-100 px-6 py-4 last:border-0 hover:bg-gray-50">
                        <span class="flex items-center gap-3">
                            {{-- Cuadro de color de la camiseta (identificador) --}}
                            <span class="inline-block h-4 w-4 shrink-0 rounded border border-gray-300"
                                  style="background-color: {{ $equipo->color_camiseta ?: '#e5e7eb' }};"
                                  title="{{ $equipo->color_camiseta ?: 'Sin color' }}"></span>
                            <span class="font-medium text-gray-900">{{ $equipo->nombre }}</span>
                        </span>
                        <span class="text-sm text-gray-500">
                            {{ $equipo->modalidad->nombre }} ·
                            {{ $equipo->jugadores_count }}/{{ $equipo->modalidad->max_jugadores }} jugadores
                        </span>
                    </a>
                @empty
                    <p class="px-6 py-4 text-sm text-gray-500">Todavía no hay equipos.</p>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>
