{{-- Errores del archivo: generales y por fila --}}
@error('archivo')
    <div class="rounded-md bg-red-50 p-4 text-sm text-red-700">{{ $message }}</div>
@enderror
@if (session('errores_filas'))
    <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">
        <p class="font-semibold">No se cargó nada: corrige estas filas y vuelve a subir el archivo.</p>
        <ul class="mt-2 space-y-1">
            @foreach (session('errores_filas') as $fila => $mensajes)
                <li><span class="font-medium">Fila {{ $fila }}:</span> {{ implode(' ', $mensajes) }}</li>
            @endforeach
        </ul>
    </div>
@endif
