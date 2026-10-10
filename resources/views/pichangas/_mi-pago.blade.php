{{-- Pago del jugador para una fecha: ver monto, datos para transferir y subir comprobante.
     Requiere: $pichanga, $fecha, $miAsistencia, $porPersona --}}
@if ($porPersona && $miAsistencia?->estado === 'confirmado' && $fecha->estado !== 'cancelada')
    <div class="rounded-md border p-4 {{ $miAsistencia->pagado ? 'border-green-200 bg-green-50' : 'border-yellow-200 bg-yellow-50' }}"
         x-data="{ copiado: false }">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="text-sm">
                <span class="font-semibold text-gray-800">Tu pago: ${{ number_format($porPersona, 0, ',', '.') }}</span>
                @if ($miAsistencia->pagado)
                    <span class="ms-2 font-medium text-green-700">✓ Pagado</span>
                    <span class="text-xs text-gray-500">
                        @if ($miAsistencia->comprobante_path)
                            (comprobante subido el {{ $miAsistencia->comprobante_subido_en->format('d/m H:i') }})
                        @else
                            (marcado por el organizador)
                        @endif
                    </span>
                @else
                    <span class="ms-2 font-medium text-yellow-700">Pendiente</span>
                @endif
            </div>
            @if ($miAsistencia->comprobante_path)
                <a href="{{ route('pichangas.comprobante.show', $miAsistencia) }}" target="_blank"
                   class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Ver mi comprobante</a>
            @endif
        </div>

        @if ($pichanga->datos_transferencia)
            <div class="mt-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium uppercase tracking-wide text-gray-500">Datos para transferir</span>
                    <button type="button" class="text-xs font-medium text-indigo-600 hover:text-indigo-800"
                            @click="navigator.clipboard.writeText($refs.datos.innerText); copiado = true; setTimeout(() => copiado = false, 2000)"
                            x-text="copiado ? '¡Copiado!' : 'Copiar'">Copiar</button>
                </div>
                <pre x-ref="datos" class="mt-1 whitespace-pre-wrap rounded bg-white p-3 font-sans text-sm text-gray-800">{{ $pichanga->datos_transferencia }}</pre>
            </div>
        @endif

        <form method="POST" action="{{ route('pichangas.comprobante.store', $fecha) }}" enctype="multipart/form-data"
              class="mt-3 flex flex-wrap items-center gap-2">
            @csrf
            <input type="file" name="comprobante" accept="image/*" required
                   class="block w-full text-sm text-gray-700 file:me-3 file:rounded-md file:border-0 file:bg-white file:px-3 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-100 sm:w-auto sm:flex-1">
            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                {{ $miAsistencia->comprobante_path ? 'Reemplazar comprobante' : 'Subir comprobante' }}
            </button>
        </form>
        @unless ($miAsistencia->pagado)
            <p class="mt-2 text-xs text-gray-500">Transfiere y sube la captura: quedas como pagado al instante.</p>
        @endunless
    </div>
@endif
