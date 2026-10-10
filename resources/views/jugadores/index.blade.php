<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Jugadores</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>
            @endif

            <form method="GET" action="{{ route('jugadores.index') }}" class="flex gap-3">
                <input type="text" name="q" value="{{ $busqueda }}" placeholder="Buscar por jugador o equipo..."
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <button type="submit"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    Buscar
                </button>
                @if ($busqueda !== '')
                    <a href="{{ route('jugadores.index') }}"
                       class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Limpiar
                    </a>
                @endif
            </form>

            @forelse ($jugadores as $nombreEquipo => $delEquipo)
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <h3 class="border-b border-gray-100 px-6 py-3 text-sm font-medium text-gray-700">
                        {{ $nombreEquipo }}
                    </h3>

                    @foreach ($delEquipo as $jugador)
                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-3 last:border-0">
                            <div class="flex items-center gap-3">
                                @if ($jugador->foto)
                                    <img src="{{ asset($jugador->foto) }}?t={{ $jugador->updated_at?->timestamp }}"
                                         alt="" class="h-10 w-10 rounded-full object-cover border border-gray-200">
                                @else
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-xs font-medium text-gray-500">
                                        {{ \Illuminate\Support\Str::of($jugador->nombre)->explode(' ')->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}
                                    </div>
                                @endif
                                <div class="text-sm">
                                    <div class="font-medium text-gray-900">
                                        {{ $jugador->nombre }}
                                        @if ($jugador->dorsal)
                                            <span class="text-gray-400">#{{ $jugador->dorsal }}</span>
                                        @endif
                                    </div>
                                    @if ($jugador->posicion)
                                        <div class="text-xs text-gray-500">{{ $jugador->posicion }}</div>
                                    @endif
                                </div>
                            </div>

                            <a href="{{ route('jugadores.edit', $jugador) }}"
                               class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                Editar
                            </a>
                        </div>
                    @endforeach
                </div>
            @empty
                <div class="bg-white p-6 text-sm text-gray-500 shadow-sm sm:rounded-lg">
                    No se encontraron jugadores.
                </div>
            @endforelse

        </div>
    </div>
</x-app-layout>
