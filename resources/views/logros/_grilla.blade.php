{{-- Grilla de insignias. Recibe: $grilla (Insignias::grilla) --}}
@php
    $nivelClases = [
        'oro' => 'bg-yellow-100 text-yellow-800',
        'plata' => 'bg-gray-100 text-gray-700',
        'bronce' => 'bg-orange-100 text-orange-800',
    ];
    $obtenidos = $grilla->filter(fn ($i) => $i['obtenido'])->count();
@endphp
<div>
    <div class="mb-3 flex items-baseline justify-between">
        <p class="text-sm text-gray-600"><span class="marcador text-2xl text-balon">{{ $obtenidos }}</span> de {{ $grilla->count() }} logros</p>
        <div class="h-2 w-32 overflow-hidden rounded-full bg-gray-100">
            <div class="h-full rounded-full bg-cancha-500" style="width: {{ $grilla->count() ? round($obtenidos * 100 / $grilla->count()) : 0 }}%"></div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
        @foreach ($grilla as $i)
            <div class="relative flex flex-col items-center rounded-lg border p-4 text-center {{ $i['obtenido'] ? 'border-cancha-200 bg-white shadow-sm' : 'border-dashed border-gray-200 bg-gray-50' }}">
                @if ($i['pro'])
                    <span class="absolute right-2 top-2 text-xs" title="Logro Pro">⭐</span>
                @endif
                @if ($i['veces'] > 1)
                    <span class="absolute left-2 top-2 rounded-full bg-balon px-1.5 text-xs font-semibold text-white">×{{ $i['veces'] }}</span>
                @endif

                <div class="text-4xl {{ $i['obtenido'] ? '' : 'opacity-40 grayscale' }}">{{ $i['icono'] }}</div>
                <div class="mt-2 text-sm font-semibold {{ $i['obtenido'] ? 'text-gray-900' : 'text-gray-500' }}">{{ $i['nombre'] }}</div>
                <div class="mt-0.5 text-xs text-gray-500">{{ $i['descripcion'] }}</div>

                <div class="mt-auto w-full pt-3">
                    @if ($i['obtenido'])
                        <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $nivelClases[$i['nivel']] }}">{{ ucfirst($i['nivel']) }}</span>
                        <div class="mt-1 text-[11px] text-gray-400">{{ $i['obtenido']->obtenido_en->format('d/m/Y') }}</div>
                    @elseif ($i['bloqueado_pro'])
                        <div class="text-[11px] font-medium text-amber-700">⭐ Solo en campeonatos Pro</div>
                    @elseif ($i['avance'])
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200">
                            <div class="h-full rounded-full bg-cancha-500" style="width: {{ round($i['avance']['actual'] * 100 / $i['avance']['meta']) }}%"></div>
                        </div>
                        <div class="mt-1 text-[11px] text-gray-500">{{ $i['avance']['texto'] }}</div>
                    @else
                        <div class="text-[11px] text-gray-400">Por desbloquear</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
