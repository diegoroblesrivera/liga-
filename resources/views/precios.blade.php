<x-publico-layout>
    <x-slot name="title">Planes y precios · {{ config('app.name') }}</x-slot>

    @php
        $pesos = '$'.number_format($precio, 0, ',', '.');
        // [función, gratis, pro]: true = incluido, texto = detalle
        $comparacion = [
            ['Pichangas semanales', 'Sin límite', 'Sin límite'],
            ['Equipos por campeonato', "Hasta {$limite}", 'Ilimitados'],
            ['Formatos: todos contra todos, grupos y eliminación directa', true, true],
            ['Fixture, tabla de posiciones, llaves y goleadores', true, true],
            ['Árbitros: goles, tarjetas, penales y suspensiones', true, true],
            ['Lista de asistencia con QR', true, true],
            ['Carga de equipos y planteles por Excel', true, true],
            ['Invitaciones por correo y WhatsApp', true, true],
            ['Control de pagos con comprobantes', true, true],
            ['Página pública del campeonato', true, true],
            ['Logros para todos (primer gol, hat-trick, 100 partidos...)', true, true],
            ['Logros Pro (Campeón, Pichichi, Racha goleadora...)', false, true],
        ];
    @endphp

    <section class="text-center">
        <p class="text-xs font-semibold uppercase tracking-widest text-cancha-600">Planes y precios</p>
        <h1 class="titulo-deportivo mt-1 text-3xl text-balon sm:text-4xl">Simple: gratis hasta {{ $limite }} equipos</h1>
        <p class="mx-auto mt-3 max-w-2xl text-gray-600">
            Todas las funciones están en el plan Gratis. Cuando tu campeonato crece, el plan Pro le quita el límite de equipos
            y suma los logros más codiciados. <strong>Solo paga el organizador</strong>: jugadores, DT y árbitros usan su cuenta gratis.
        </p>
    </section>

    {{-- Tarjetas de planes --}}
    <section class="mx-auto mt-8 grid max-w-4xl gap-6 md:grid-cols-2">
        <div class="flex flex-col rounded-2xl bg-white p-7 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">Gratis</h2>
            <p class="mt-1 text-sm text-gray-500">Para pichangas y campeonatos chicos.</p>
            <p class="marcador mt-4 text-5xl text-balon">$0</p>
            <ul class="mt-5 space-y-2 text-sm text-gray-700">
                <li>✓ Pichangas sin límite</li>
                <li>✓ Campeonatos de hasta <strong>{{ $limite }} equipos</strong></li>
                <li>✓ Todas las funciones de campeonato</li>
                <li>✓ Logros básicos para los jugadores</li>
            </ul>
            <a href="{{ route('campeonatos.create') }}" class="mt-auto block rounded-lg border-2 border-balon px-5 py-3 text-center font-bold text-balon hover:bg-gray-50" style="margin-top: 1.75rem">Empezar gratis</a>
        </div>

        <div class="relative flex flex-col rounded-2xl bg-balon p-7 text-white shadow-md ring-2 ring-cancha-400">
            <span class="absolute -top-3 left-7 rounded-full bg-cancha-400 px-3 py-0.5 text-xs font-bold text-balon">Más completo</span>
            <h2 class="text-lg font-semibold">Pro</h2>
            <p class="mt-1 text-sm text-gray-300">Por campeonato, un pago al año.</p>
            <p class="marcador mt-4 text-5xl text-cancha-300">{{ $pesos }}<span class="text-lg text-gray-300"> / año</span></p>
            <ul class="mt-5 space-y-2 text-sm text-gray-100">
                <li>✓ Todo lo del plan Gratis</li>
                <li>✓ <strong>Equipos ilimitados</strong></li>
                <li>✓ Logros Pro para tus jugadores</li>
                <li>✓ Pago seguro con Mercado Pago</li>
            </ul>
            <a href="{{ route('campeonatos.create') }}" class="mt-auto block rounded-lg bg-cancha-400 px-5 py-3 text-center font-bold text-balon hover:bg-cancha-300" style="margin-top: 1.75rem">Crear campeonato y pasar a Pro</a>
        </div>
    </section>

    {{-- Comparación --}}
    <section class="mx-auto mt-12 max-w-4xl">
        <h2 class="titulo-deportivo text-center text-2xl text-balon">Comparación</h2>
        <div class="mt-4 overflow-hidden rounded-lg bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Función</th>
                        <th class="w-24 px-2 py-3 text-center sm:w-32">Gratis</th>
                        <th class="w-24 px-2 py-3 text-center text-cancha-700 sm:w-32">Pro</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($comparacion as [$funcion, $gratis, $pro])
                        <tr>
                            <td class="px-4 py-2.5 text-gray-700">{{ $funcion }}</td>
                            @foreach ([$gratis, $pro] as $valor)
                                <td class="px-2 py-2.5 text-center">
                                    @if ($valor === true)
                                        <span class="font-bold text-cancha-600" aria-label="Incluido">✓</span>
                                    @elseif ($valor === false)
                                        <span class="text-gray-300" aria-label="No incluido">—</span>
                                    @else
                                        <span class="font-medium text-gray-800">{{ $valor }}</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Logros Pro --}}
    <section class="mx-auto mt-12 max-w-4xl text-center">
        <h2 class="titulo-deportivo text-2xl text-balon">Logros que solo da el Pro</h2>
        <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-5">
            @foreach ($logrosPro as $l)
                <div class="rounded-lg bg-white p-4 shadow-sm">
                    <div class="text-3xl">{{ $l['icono'] }}</div>
                    <div class="mt-1 text-sm font-semibold text-gray-900">{{ $l['nombre'] }}</div>
                    <div class="mt-0.5 text-xs text-gray-500">{{ $l['descripcion'] }}</div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Preguntas frecuentes --}}
    <section class="mx-auto mt-12 max-w-3xl">
        <h2 class="titulo-deportivo text-center text-2xl text-balon">Preguntas frecuentes</h2>
        <div class="mt-4 divide-y divide-gray-200 rounded-lg bg-white shadow-sm">
            @foreach ([
                ['¿Quién paga el plan Pro?', 'Solo el organizador (dueño o administrador) del campeonato. Los jugadores, DT y árbitros entran con su cuenta gratis.'],
                ['¿Es por campeonato o por cuenta?', 'Por campeonato. Si organizas dos campeonatos y ambos superan los '.$limite.' equipos, cada uno necesita su plan Pro.'],
                ['¿Cómo se paga?', 'Con Mercado Pago (tarjeta de crédito, débito u otros medios), desde Configuración → Plan del campeonato. Se activa apenas se aprueba el pago.'],
                ['¿Qué pasa cuando vence el año?', 'El campeonato vuelve al plan Gratis: no se borra nada y los logros ya ganados se mantienen. Solo no podrás agregar equipos sobre el límite hasta renovar.'],
                ['¿Puedo renovar antes de que venza?', 'Sí. El año nuevo se suma desde la fecha de vencimiento, así no pierdes días.'],
                ['¿Las pichangas tienen costo?', 'No. Las pichangas semanales son gratis y sin límite.'],
            ] as [$pregunta, $respuesta])
                <details class="group p-4">
                    <summary class="flex cursor-pointer list-none items-center justify-between font-medium text-gray-900">
                        {{ $pregunta }}
                        <span class="ms-3 text-gray-400 transition group-open:rotate-45">＋</span>
                    </summary>
                    <p class="mt-2 text-sm text-gray-600">{{ $respuesta }}</p>
                </details>
            @endforeach
        </div>
        <p class="mt-6 text-center text-sm text-gray-500">¿Otra duda? Escríbenos a <a href="mailto:{{ config('app.correo_privacidad') }}" class="font-medium text-indigo-600 hover:text-indigo-800">{{ config('app.correo_privacidad') }}</a>.</p>
    </section>
</x-publico-layout>
