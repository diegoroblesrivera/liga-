<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Panel</h2>
    </x-slot>

    @php($equipoDt = auth()->user()?->equipo)

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">

            @if (session('status') && is_string(session('status')) && session('status') !== 'profile-updated')
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>
            @endif

            {{-- Panel del DT: su equipo y el link de jugadores --}}
            @if ($equipoDt)
                {{-- Link recién generado --}}
                @if (session('link_invitacion'))
                    <div class="rounded-md bg-indigo-50 p-4">
                        <p class="mb-2 text-sm font-medium text-indigo-800">
                            Link de jugadores generado — compártelo con tu plantel:
                        </p>
                        <div class="flex gap-2">
                            <input id="link-invitacion" type="text" readonly
                                   value="{{ session('link_invitacion') }}"
                                   class="w-full rounded-md border-gray-300 bg-white text-sm text-gray-700">
                            <button type="button" onclick="copiarLink()"
                                    class="shrink-0 rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                Copiar
                            </button>
                        </div>
                    </div>
                @endif

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            @if ($equipoDt->escudo)
                                <img src="{{ asset($equipoDt->escudo) }}?t={{ $equipoDt->updated_at?->timestamp }}"
                                     alt="Escudo" class="h-10 w-10 rounded object-cover border border-gray-200">
                            @endif
                            <div>
                                <h3 class="text-base font-medium text-gray-900">{{ $equipoDt->nombre }}</h3>
                                <p class="text-sm text-gray-500">
                                    {{ $equipoDt->jugadores()->count() }} / {{ $equipoDt->modalidad->max_jugadores }} jugadores
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('equipos.show', $equipoDt) }}"
                           class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                            Ver mi equipo
                        </a>
                    </div>

                    <div class="mt-5 border-t border-gray-100 pt-5">
                        <p class="mb-3 text-sm text-gray-600">
                            Genera el link de inscripción y compártelo con tus jugadores para que se sumen al equipo.
                        </p>
                        @can('create', [App\Models\Invitacion::class, $equipoDt, 'jugador'])
                            <form method="POST" action="{{ route('invitaciones.store', $equipoDt) }}">
                                @csrf
                                <input type="hidden" name="tipo" value="jugador">
                                <x-primary-button>Generar link de jugadores</x-primary-button>
                            </form>
                        @endcan
                    </div>
                </div>
            @else
                <div class="bg-white p-6 text-sm text-gray-600 shadow-sm sm:rounded-lg">
                    Bienvenido. Usa el menú de arriba para navegar por la liga.
                </div>
            @endif

        </div>
    </div>

    <script>
        function copiarLink() {
            const input = document.getElementById('link-invitacion');
            if (!input) return;
            input.select();
            navigator.clipboard.writeText(input.value);
        }
    </script>
</x-app-layout>
