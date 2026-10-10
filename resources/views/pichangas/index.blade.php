<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Mis pichangas</h2>
            <a href="{{ route('pichangas.create') }}"
               class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                Nueva pichanga
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">

            @include('pichangas._alertas')

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                @forelse ($pichangas as $pichanga)
                    <a href="{{ route('pichangas.show', $pichanga) }}"
                       class="flex items-center justify-between border-b border-gray-100 px-6 py-4 last:border-0 hover:bg-gray-50">
                        <div>
                            <div class="flex items-center gap-2 font-medium text-gray-900">
                                {{ $pichanga->nombre }}
                                @if ($pichanga->pivot->rol === 'organizador')
                                    <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">Organizador</span>
                                @endif
                                @unless ($pichanga->activa)
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">Pausada</span>
                                @endunless
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $pichanga->nombreDia() }} {{ $pichanga->horaCorta() }}
                                @if ($pichanga->lugar) · {{ $pichanga->lugar }} @endif
                            </div>
                        </div>
                        <div class="text-right text-xs text-gray-400">
                            {{ $pichanga->miembros_count }} {{ \Illuminate\Support\Str::plural('miembro', $pichanga->miembros_count) }}<br>
                            cupo {{ $pichanga->cupo }}
                        </div>
                    </a>
                @empty
                    <div class="px-6 py-10 text-center">
                        <p class="text-sm text-gray-500">Todavía no estás en ninguna pichanga.</p>
                        <p class="mt-1 text-sm text-gray-500">Crea la tuya o pídele el link de invitación al organizador.</p>
                    </div>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>
