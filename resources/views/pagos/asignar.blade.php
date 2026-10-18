<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Asignar monto de pago — {{ $partido->equipoLocal->nombre }} vs {{ $partido->equipoVisita->nombre }}
        </h2>
    </x-slot>

    <div class="py-8 max-w-2xl mx-auto sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
        @endif

        <div class="bg-white shadow rounded-lg p-6">
            <p class="text-sm text-gray-500 mb-4">
                Fecha {{ $partido->numero_fecha }} · {{ $partido->fecha_hora?->format('d/m/Y H:i') }}
            </p>

            <form method="POST" action="{{ route('partidos.pago.update', $partido) }}">
                @csrf
                @method('PUT')

                <label class="block text-sm font-medium text-gray-700 mb-1">Monto a pagar por equipo (CLP)</label>
                <input type="number" step="0.01" min="0" name="monto_pago"
                       value="{{ old('monto_pago', $partido->monto_pago) }}"
                       class="w-full border-gray-300 rounded-md shadow-sm">
                @error('monto_pago')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
                <p class="text-xs text-gray-400 mt-1">Este monto se aplica por igual a ambos equipos del partido.</p>

                <button type="submit" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    Guardar monto
                </button>
            </form>

            <hr class="my-6">

            <h3 class="font-medium text-gray-800 mb-2">Estado de comprobantes</h3>
            <ul class="space-y-2">
                @foreach ([$partido->equipoLocal, $partido->equipoVisita] as $equipo)
                    @php($pago = $partido->pagos->firstWhere('equipo_id', $equipo->id))
                    <li class="flex items-center justify-between border rounded p-3">
                        <span>{{ $equipo->nombre }}</span>
                        @if ($pago)
                            <a href="{{ route('pagos.comprobante.ver', $pago) }}" target="_blank"
                               class="text-xs px-2 py-1 bg-green-100 text-green-800 rounded">Ver comprobante</a>
                        @else
                            <span class="text-xs px-2 py-1 bg-yellow-100 text-yellow-800 rounded">Pendiente</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</x-app-layout>
