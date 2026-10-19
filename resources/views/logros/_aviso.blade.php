{{-- Aviso de logros nuevos (una sola vez: se marcan vistos al mostrarse) --}}
@php
    $logrosNuevos = auth()->check() ? \App\Support\Logros\Insignias::tomarNuevos(auth()->user()) : collect();
@endphp
@if ($logrosNuevos->isNotEmpty())
    <div class="mx-auto mt-4 max-w-7xl px-4 sm:px-6 lg:px-8" x-data="{ ver: true }" x-show="ver">
        <div class="flex items-start gap-4 rounded-lg bg-cancha-50 p-4 shadow-sm ring-1 ring-cancha-200">
            <div class="text-3xl">🎉</div>
            <div class="flex-1 text-sm">
                @if ($logrosNuevos->count() === 1)
                    @php
                        $d = $logrosNuevos->first()->definicion();
                    @endphp
                    <p class="font-semibold text-gray-900">¡Desbloqueaste {{ $d['icono'] }} {{ $d['nombre'] }}!</p>
                    <p class="text-gray-600">{{ $d['descripcion'] }}</p>
                @else
                    <p class="font-semibold text-gray-900">¡Tienes {{ $logrosNuevos->count() }} logros nuevos!</p>
                    <p class="mt-1 text-xl">
                        @foreach ($logrosNuevos->map->definicion()->unique('clave')->take(8) as $d)
                            <span title="{{ $d['nombre'] }}">{{ $d['icono'] }}</span>
                        @endforeach
                    </p>
                @endif
                <a href="{{ route('logros.mios') }}" class="mt-1 inline-block font-medium text-cancha-700 hover:text-cancha-800">Ver mis logros →</a>
            </div>
            <button type="button" @click="ver = false" class="text-gray-400 hover:text-gray-600" aria-label="Cerrar">✕</button>
        </div>
    </div>
@endif
