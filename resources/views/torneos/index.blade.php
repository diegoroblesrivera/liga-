<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Torneos</h2>
            <a href="{{ route('torneos.create') }}"
               class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                Nuevo torneo
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                @forelse ($torneos as $torneo)
                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 last:border-0">
                        <div>
                            <div class="font-medium text-gray-900">
                                {{ $torneo->nombre }}
                                @if ($torneo->temporada)
                                    <span class="text-gray-400">· {{ $torneo->temporada }}</span>
                                @endif
                            </div>
                            <div class="text-sm text-gray-500">
                                @if ($torneo->fecha_inicio || $torneo->fecha_fin)
                                    {{ $torneo->fecha_inicio?->format('d/m/Y') ?? '—' }}
                                    al
                                    {{ $torneo->fecha_fin?->format('d/m/Y') ?? '—' }}
                                @else
                                    Sin fechas definidas
                                @endif
                            </div>
                            <div class="mt-1 text-xs text-gray-400">
                                {{ $torneo->equipos_count }} {{ \Illuminate\Support\Str::plural('equipo', $torneo->equipos_count) }}
                                · {{ $torneo->partidos_count }} {{ \Illuminate\Support\Str::plural('partido', $torneo->partidos_count) }}
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <a href="{{ route('torneos.edit', $torneo) }}"
                               class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Editar</a>
                            <form method="POST" action="{{ route('torneos.destroy', $torneo) }}"
                                  onsubmit="return confirm('¿Eliminar este torneo?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800">Eliminar</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="px-6 py-4 text-sm text-gray-500">Aún no hay torneos registrados.</p>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>
