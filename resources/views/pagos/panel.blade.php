<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Pagos por fecha</h2>

            @if ($torneo)
                <div class="flex items-center gap-2">
                    <a href="{{ route('pagos.export.excel', ['torneo' => $torneo->id]) }}"
                       class="rounded-md bg-green-600 px-3 py-2 text-sm font-medium text-white hover:bg-green-700">
                        Exportar Excel
                    </a>
                    <a href="{{ route('pagos.export.pdf', ['torneo' => $torneo->id]) }}"
                       class="rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700">
                        Exportar PDF
                    </a>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>
            @endif

            {{-- Selector de torneo --}}
            @if ($torneos->count() > 0)
                <form method="GET">
                    <label for="torneo" class="mr-2 text-sm text-gray-600">Torneo:</label>
                    <select id="torneo" name="torneo" onchange="this.form.submit()"
                            class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($torneos as $t)
                            <option value="{{ $t->id }}" @selected($torneo && $torneo->id === $t->id)>{{ $t->nombre }}</option>
                        @endforeach
                    </select>
                </form>
            @endif

            @forelse ($jornadas as $numero => $partidos)
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <h3 class="border-b border-gray-100 px-5 py-3 text-sm font-semibold text-gray-800">
                        Fecha {{ $numero }}
                    </h3>

                    <div class="divide-y divide-gray-100">
                        @foreach ($partidos as $partido)
                            @php
                                $pagoLocal = $partido->pagos->firstWhere('equipo_id', $partido->equipo_local_id);
                                $pagoVisita = $partido->pagos->firstWhere('equipo_id', $partido->equipo_visita_id);
                            @endphp

                            <div class="px-5 py-4">
                                <div class="mb-2 flex items-center justify-between text-xs text-gray-500">
                                    <span>{{ $partido->fecha_hora?->format('d/m/Y H:i') ?? 'Sin fecha' }}</span>
                                    <span>Monto: ${{ number_format($partido->monto_pago, 0, ',', '.') }}</span>
                                </div>

                                <div class="flex items-center justify-between py-1 text-sm">
                                    <span class="font-medium text-gray-900">{{ $partido->equipoLocal->nombre }}</span>
                                    <span class="flex items-center gap-3">
                                        @if ($pagoLocal && $pagoLocal->comprobante_path)
                                            <span>🟢 Pagado</span>
                                            <a href="{{ route('pagos.comprobante.ver', $pagoLocal) }}" target="_blank"
                                               class="text-indigo-600 hover:text-indigo-800">Ver comprobante</a>
                                        @else
                                            <span>🔴 Pendiente</span>
                                        @endif
                                    </span>
                                </div>

                                <div class="flex items-center justify-between py-1 text-sm">
                                    <span class="font-medium text-gray-900">{{ $partido->equipoVisita->nombre }}</span>
                                    <span class="flex items-center gap-3">
                                        @if ($pagoVisita && $pagoVisita->comprobante_path)
                                            <span>🟢 Pagado</span>
                                            <a href="{{ route('pagos.comprobante.ver', $pagoVisita) }}" target="_blank"
                                               class="text-indigo-600 hover:text-indigo-800">Ver comprobante</a>
                                        @else
                                            <span>🔴 Pendiente</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="bg-white p-6 text-sm text-gray-500 shadow-sm sm:rounded-lg">
                    @if ($torneo)
                        Este torneo todavía no tiene partidos con monto de pago asignado.
                    @else
                        No hay torneos para mostrar.
                    @endif
                </div>
            @endforelse

            {{-- Paginación por jornada --}}
            @if ($paginaJornadas && $paginaJornadas->hasPages())
                <div>
                    {{ $paginaJornadas->links() }}
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
