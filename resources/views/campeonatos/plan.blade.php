<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Plan del campeonato</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>
            @endif
            @if (session('aviso'))
                <div class="rounded-md bg-yellow-50 p-4 text-sm text-yellow-800">{{ session('aviso') }}</div>
            @endif

            {{-- Estado actual --}}
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm text-gray-500">{{ $liga->nombre }}</p>
                        @if ($liga->esPro())
                            <p class="titulo-deportivo mt-1 text-2xl text-cancha-600">Plan Pro</p>
                            <p class="text-sm text-gray-600">Vigente hasta el <strong>{{ $liga->pro_hasta->format('d/m/Y') }}</strong> · equipos ilimitados ({{ $equipos }} creados)</p>
                        @else
                            <p class="titulo-deportivo mt-1 text-2xl text-balon">Plan Gratis</p>
                            <p class="text-sm text-gray-600">
                                <strong>{{ $equipos }} de {{ \App\Models\Liga::EQUIPOS_PLAN_GRATIS }}</strong> equipos usados
                                @if ($liga->pro_hasta)
                                    · tu Pro venció el {{ $liga->pro_hasta->format('d/m/Y') }}
                                @endif
                            </p>
                        @endif
                    </div>
                    @unless ($liga->esPro())
                        <div class="h-2 w-40 overflow-hidden rounded-full bg-gray-100" title="{{ $equipos }} de {{ \App\Models\Liga::EQUIPOS_PLAN_GRATIS }} equipos">
                            <div class="h-2 rounded-full {{ $equipos >= \App\Models\Liga::EQUIPOS_PLAN_GRATIS ? 'bg-red-500' : 'bg-cancha-400' }}"
                                 style="width: {{ min(100, round($equipos / \App\Models\Liga::EQUIPOS_PLAN_GRATIS * 100)) }}%"></div>
                        </div>
                    @endunless
                </div>
            </div>

            {{-- Comparación de planes --}}
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="flex flex-col rounded-lg border-t-4 border-gray-300 bg-white p-6 shadow-sm">
                    <h3 class="titulo-deportivo text-xl text-balon">Gratis</h3>
                    <p class="marcador mt-2 text-4xl text-balon">$0</p>
                    <ul class="mt-4 flex-1 space-y-2 text-sm text-gray-600">
                        <li>✓ Hasta <strong>{{ \App\Models\Liga::EQUIPOS_PLAN_GRATIS }} equipos</strong></li>
                        <li>✓ Fixture automático, resultados, tabla y goleadores</li>
                        <li>✓ DT, jugadores y árbitros con cuenta</li>
                        <li>✓ Control de pagos y exportación a Excel/PDF</li>
                        <li>✓ Página pública del campeonato</li>
                    </ul>
                </div>

                <div class="flex flex-col rounded-lg border-t-4 border-cancha-400 bg-white p-6 shadow-sm ring-1 ring-cancha-100">
                    <h3 class="titulo-deportivo text-xl text-cancha-600">Pro</h3>
                    <p class="mt-2"><span class="marcador text-4xl text-balon">${{ number_format($precio, 0, ',', '.') }}</span> <span class="text-sm text-gray-500">al año</span></p>
                    <ul class="mt-4 flex-1 space-y-2 text-sm text-gray-600">
                        <li>✓ <strong>Equipos ilimitados</strong></li>
                        <li>✓ Todo lo del plan Gratis</li>
                        <li>✓ Un solo pago al año: solo lo paga el admin del campeonato</li>
                        <li>✓ DT, jugadores y árbitros siguen usando su cuenta gratis</li>
                    </ul>

                    <div class="mt-6">
                        @if ($pagoDisponible)
                            <form method="POST" action="{{ route('campeonatos.plan.pagar') }}">
                                @csrf
                                <button type="submit" class="w-full rounded-md bg-cancha-600 px-4 py-3 text-sm font-semibold text-white hover:bg-cancha-700">
                                    {{ $liga->esPro() ? 'Renovar un año más' : 'Pasar a Pro' }} con Mercado Pago
                                </button>
                            </form>
                            <p class="mt-2 text-center text-xs text-gray-500">
                                Pago seguro en Mercado Pago (tarjeta de crédito, débito o saldo).
                                @if ($liga->esPro()) El año nuevo se suma al final del actual. @endif
                            </p>
                            @if ($modoPrueba)
                                <p class="mt-2 rounded bg-yellow-50 p-2 text-center text-xs text-yellow-800">Modo de prueba: no se cobra dinero real.</p>
                            @endif
                        @else
                            <p class="rounded-md bg-gray-50 p-3 text-center text-sm text-gray-600">
                                El pago en línea estará disponible muy pronto. Para pasar a Pro ahora, escríbenos a
                                <a href="mailto:{{ config('app.correo_privacidad') }}" class="font-medium text-cancha-600 underline">{{ config('app.correo_privacidad') }}</a>.
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Historial --}}
            @if ($pagos->isNotEmpty())
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-sm font-semibold text-gray-700">Historial del plan Pro</h3>
                    <table class="mt-3 w-full text-sm">
                        <thead class="text-left text-xs uppercase text-gray-400">
                            <tr><th class="py-1">Fecha</th><th class="py-1">Período</th><th class="py-1 text-right">Monto</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($pagos as $p)
                                <tr class="border-t border-gray-100">
                                    <td class="py-2">{{ $p->pagado_en?->format('d/m/Y') }}</td>
                                    <td class="py-2 text-gray-600">{{ $p->pro_desde?->format('d/m/Y') }} – {{ $p->pro_hasta?->format('d/m/Y') }}</td>
                                    <td class="py-2 text-right">{{ $p->monto ? '$'.number_format($p->monto, 0, ',', '.') : 'Cortesía' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
