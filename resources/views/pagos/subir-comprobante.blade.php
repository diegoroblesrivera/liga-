<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Subir comprobante — {{ $partido->equipoLocal->nombre }} vs {{ $partido->equipoVisita->nombre }}
        </h2>
    </x-slot>

    <div class="py-8 max-w-xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg p-6">
            <p class="text-sm text-gray-600 mb-4">
                Monto a pagar: <strong>${{ number_format($partido->monto_pago, 0, ',', '.') }}</strong>
            </p>

            @if ($pago)
                <div class="mb-4">
                    <p class="text-sm text-gray-500 mb-1">Comprobante actual:</p>
                    <img src="{{ route('pagos.comprobante.ver', $pago) }}" class="max-h-64 rounded border">
                </div>
            @endif

            <form method="POST" action="{{ route('pagos.comprobante.store', $partido) }}" enctype="multipart/form-data">
                @csrf
                <input type="file" name="comprobante" accept="image/*" required class="block w-full text-sm">
                @error('comprobante')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror

                <button type="submit" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    {{ $pago ? 'Reemplazar comprobante' : 'Subir comprobante' }}
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
