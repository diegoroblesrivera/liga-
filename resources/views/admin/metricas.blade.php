<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Panel TuTorneo · Métricas</h2>
            <a href="{{ route('admin.campeonatos') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Campeonatos y Pro →</a>
        </div>
    </x-slot>

    @php
        $tile = 'bg-white p-4 shadow-sm sm:rounded-lg';
        $pesos = fn ($n) => '$'.number_format($n, 0, ',', '.');
        $graficos = [
            'activos' => ['Usuarios activos por semana', 'bg-cancha-500'],
            'nuevos' => ['Usuarios nuevos por semana', 'bg-indigo-400'],
            'partidos' => ['Partidos jugados por semana', 'bg-balon'],
        ];
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">

            {{-- Usuarios --}}
            <section>
                <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-gray-500">Usuarios</h3>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ([
                        ['Registrados', $usuarios['total']],
                        ['Nuevos (7 días)', $usuarios['nuevos_7']],
                        ['Nuevos (30 días)', $usuarios['nuevos_30']],
                        ['Activos hoy', $usuarios['activos_1']],
                        ['Activos (7 días)', $usuarios['activos_7']],
                        ['Activos (30 días)', $usuarios['activos_30']],
                    ] as [$titulo, $valor])
                        <div class="{{ $tile }}">
                            <p class="text-xs text-gray-400">{{ $titulo }}</p>
                            <p class="marcador mt-1 text-3xl text-balon">{{ $valor }}</p>
                        </div>
                    @endforeach
                </div>
                <p class="mt-1 text-xs text-gray-400">"Activo" = entró con su cuenta ese día. Se registra desde que se activó el panel.</p>
            </section>

            {{-- Campeonatos, actividad e ingresos --}}
            <section class="grid gap-3 lg:grid-cols-3">
                <div class="{{ $tile }}">
                    <h3 class="mb-3 text-sm font-semibold text-gray-900">🏆 Campeonatos</h3>
                    <dl class="grid grid-cols-2 gap-y-2 text-sm">
                        <dt class="text-gray-500">Total</dt><dd class="text-right font-semibold">{{ $campeonatos['total'] }}</dd>
                        <dt class="text-gray-500">Activos (30 días)</dt><dd class="text-right font-semibold">{{ $campeonatos['activos_30'] }}</dd>
                        <dt class="text-gray-500">Pro vigentes</dt><dd class="text-right font-semibold text-cancha-700">{{ $campeonatos['pro'] }}</dd>
                        <dt class="text-gray-500">Nuevos (30 días)</dt><dd class="text-right font-semibold">{{ $campeonatos['nuevos_30'] }}</dd>
                    </dl>
                </div>
                <div class="{{ $tile }}">
                    <h3 class="mb-3 text-sm font-semibold text-gray-900">⚽ Uso (últimos 30 días)</h3>
                    <dl class="grid grid-cols-2 gap-y-2 text-sm">
                        <dt class="text-gray-500">Partidos jugados</dt><dd class="text-right font-semibold">{{ $actividad['partidos'] }}</dd>
                        <dt class="text-gray-500">Goles cargados</dt><dd class="text-right font-semibold">{{ $actividad['goles'] }}</dd>
                        <dt class="text-gray-500">Pichangas jugadas</dt><dd class="text-right font-semibold">{{ $actividad['pichangas'] }}</dd>
                        <dt class="text-gray-500">Asistencias por lista</dt><dd class="text-right font-semibold">{{ $actividad['asistencias'] }}</dd>
                        <dt class="text-gray-500">Logros ganados</dt><dd class="text-right font-semibold">{{ $actividad['logros'] }}</dd>
                    </dl>
                </div>
                <div class="{{ $tile }}">
                    <h3 class="mb-3 text-sm font-semibold text-gray-900">💳 Ingresos Pro (Mercado Pago)</h3>
                    <dl class="grid grid-cols-2 gap-y-2 text-sm">
                        <dt class="text-gray-500">Este mes</dt><dd class="text-right font-semibold">{{ $pesos($ingresos['mes']) }}</dd>
                        <dt class="text-gray-500">Pagos este mes</dt><dd class="text-right font-semibold">{{ $ingresos['pagos_mes'] }}</dd>
                        <dt class="text-gray-500">Total histórico</dt><dd class="text-right font-semibold">{{ $pesos($ingresos['total']) }}</dd>
                    </dl>
                </div>
            </section>

            {{-- Gráficos semanales --}}
            <section class="grid gap-3 lg:grid-cols-3">
                @foreach ($graficos as $campo => [$titulo, $color])
                    @php
                        $max = max(1, max(array_column($semanas, $campo)));
                    @endphp
                    <div class="{{ $tile }}">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900">{{ $titulo }}</h3>
                        <div class="flex h-32 items-end gap-1">
                            @foreach ($semanas as $s)
                                <div class="flex h-full flex-1 flex-col justify-end" title="Semana del {{ $s['etiqueta'] }}: {{ $s[$campo] }}">
                                    <span class="mb-0.5 text-center text-[10px] text-gray-500">{{ $s[$campo] ?: '' }}</span>
                                    <div class="{{ $color }} rounded-t" style="height: {{ round($s[$campo] * 100 / $max) }}%; min-height: {{ $s[$campo] ? '2px' : '0' }}"></div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-1 flex justify-between text-[10px] text-gray-400">
                            <span>{{ $semanas[0]['etiqueta'] }}</span><span>esta semana</span>
                        </div>
                    </div>
                @endforeach
            </section>

            <section class="grid gap-3 lg:grid-cols-2">
                {{-- Oportunidades de venta --}}
                <div class="{{ $tile }}">
                    <h3 class="text-sm font-semibold text-gray-900">🎯 Gratis cerca del límite ({{ \App\Models\Liga::EQUIPOS_PLAN_GRATIS }} equipos)</h3>
                    <p class="mb-3 text-xs text-gray-500">Candidatos a pasar a Pro.</p>
                    @forelse ($cercaDelLimite as $l)
                        <div class="flex items-center justify-between border-t border-gray-100 py-2 text-sm">
                            <span>
                                <span class="font-medium text-gray-900">{{ $l->nombre }}</span>
                                <span class="block text-xs text-gray-400">{{ $l->owner?->name }} · {{ $l->owner?->email }}</span>
                            </span>
                            <span class="{{ $l->equipos_count >= \App\Models\Liga::EQUIPOS_PLAN_GRATIS ? 'text-red-600' : 'text-amber-600' }} font-semibold">{{ $l->equipos_count }}/{{ \App\Models\Liga::EQUIPOS_PLAN_GRATIS }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">Ninguno por ahora.</p>
                    @endforelse
                </div>

                {{-- Pro por vencer --}}
                <div class="{{ $tile }}">
                    <h3 class="text-sm font-semibold text-gray-900">⏳ Pro que vencen en 30 días</h3>
                    <p class="mb-3 text-xs text-gray-500">Para recordarles la renovación.</p>
                    @forelse ($porVencer as $l)
                        <div class="flex items-center justify-between border-t border-gray-100 py-2 text-sm">
                            <span>
                                <span class="font-medium text-gray-900">{{ $l->nombre }}</span>
                                <span class="block text-xs text-gray-400">{{ $l->owner?->email }}</span>
                            </span>
                            <span class="text-gray-700">{{ $l->pro_hasta->format('d/m/Y') }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">Ninguno por ahora.</p>
                    @endforelse
                </div>
            </section>

            {{-- Más activos --}}
            <section class="{{ $tile }}">
                <h3 class="mb-3 text-sm font-semibold text-gray-900">🔥 Campeonatos más activos (partidos en 30 días)</h3>
                @forelse ($masActivos as $f)
                    <div class="flex items-center justify-between border-t border-gray-100 py-2 text-sm">
                        <span>
                            <span class="font-medium text-gray-900">{{ $f['liga']->nombre }}</span>
                            <span class="ms-2 rounded-full px-2 py-0.5 text-xs font-semibold {{ $f['liga']->esPro() ? 'bg-cancha-50 text-cancha-700' : 'bg-gray-100 text-gray-600' }}">{{ $f['liga']->esPro() ? 'Pro' : 'Gratis' }}</span>
                            <span class="block text-xs text-gray-400">{{ $f['liga']->equipos_count }} equipos · {{ $f['liga']->owner?->name }}</span>
                        </span>
                        <span class="marcador text-2xl text-balon">{{ $f['partidos'] }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Sin partidos jugados en los últimos 30 días.</p>
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>
