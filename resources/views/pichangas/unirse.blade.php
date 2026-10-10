<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Invitación a pichanga</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-md sm:px-6 lg:px-8">
            <div class="bg-white p-6 text-center shadow-sm sm:rounded-lg">
                <p class="text-sm text-gray-500">{{ $pichanga->organizador?->name ?? 'Alguien' }} te invita a</p>
                <h3 class="mt-1 text-2xl font-semibold text-gray-900">{{ $pichanga->nombre }}</h3>
                <p class="mt-2 text-gray-600">
                    {{ $pichanga->nombreDia() }} {{ $pichanga->horaCorta() }}
                    @if ($pichanga->lugar) · {{ $pichanga->lugar }} @endif
                </p>
                @if ($pichanga->descripcion)
                    <p class="mt-2 text-sm text-gray-500">{{ $pichanga->descripcion }}</p>
                @endif
                <p class="mt-2 text-xs text-gray-400">
                    {{ $pichanga->miembros_count }} {{ \Illuminate\Support\Str::plural('miembro', $pichanga->miembros_count) }} · cupo {{ $pichanga->cupo }} por fecha
                </p>

                <form method="POST" action="{{ route('pichangas.unirse.store', $pichanga->token) }}" class="mt-6">
                    @csrf
                    <x-primary-button class="w-full justify-center">Unirme a la pichanga</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
