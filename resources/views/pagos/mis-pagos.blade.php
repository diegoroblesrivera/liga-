<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mis pagos</h2>
    </x-slot>

    <div class="py-8 max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-3">
        @if (session('status'))
            <div class="p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
        @endif

        @forelse ($partidos as $partido)
            @php($pago = $partido->pagos->first())
            <div class="bg-white shadow rounded-lg p-4 flex items-center justify-between">
                <div>
                    <p class="font-medium">{{ $partido->equipoLocal->nombre }} vs {{ $partido->equipoVisita->nombre }}</p>
                    <p class="text-sm text-gray-500">
                        {{ $partido->fecha_hora?->format('d/m/Y H:i') }} · Monto: ${{ number_format($partido->monto_pago, 0, ',', '.') }}
                    </p>
                </div>
                <div class="text-right">
                    @if ($pago)
                        <span class="text-xs px-3 py-1 bg-green-100 text-green-800 rounded-full">Pagado</span>
                        <a href="{{ route('pagos.comprobante.create', $partido) }}" class="block mt-1 text-sm text-indigo-600 underline">Reemplazar</a>
                    @else
                        <a href="{{ route('pagos.comprobante.create', $partido) }}"
                           class="px-3 py-1 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">Subir comprobante</a>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-gray-500">No tienes partidos con pago asignado por el momento.</p>
        @endforelse

        <div>{{ $partidos->links() }}</div>
    </div>
</x-app-layout>
