<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Panel TuTorneo · Campeonatos</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}</div>
            @endif

            {{-- Resumen y precio --}}
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="bg-white p-5 shadow-sm sm:rounded-lg">
                    <p class="text-xs uppercase text-gray-400">Campeonatos</p>
                    <p class="marcador mt-1 text-4xl text-balon">{{ $campeonatos->count() }}</p>
                </div>
                <div class="bg-white p-5 shadow-sm sm:rounded-lg">
                    <p class="text-xs uppercase text-gray-400">Pro vigentes</p>
                    <p class="marcador mt-1 text-4xl text-cancha-600">{{ $campeonatos->filter->esPro()->count() }}</p>
                </div>
                <div class="bg-white p-5 shadow-sm sm:rounded-lg">
                    <p class="text-xs uppercase text-gray-400">Cobrado con Mercado Pago</p>
                    <p class="marcador mt-1 text-4xl text-balon">${{ number_format($ingresos, 0, ',', '.') }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.precio') }}" class="flex flex-wrap items-end gap-3 bg-white p-5 shadow-sm sm:rounded-lg">
                @csrf
                @method('PUT')
                <div>
                    <x-input-label for="precio" value="Precio del Pro anual (CLP)" />
                    <x-text-input id="precio" name="precio" type="number" min="1000" step="1" class="mt-1 w-40" :value="$precio" required />
                </div>
                <x-primary-button>Guardar precio</x-primary-button>
                <p class="text-xs text-gray-500">Aplica a los pagos nuevos; no cambia los ya hechos.</p>
            </form>

            {{-- Listado --}}
            <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Campeonato</th>
                            <th class="px-4 py-3">Dueño</th>
                            <th class="px-4 py-3 text-center">Equipos</th>
                            <th class="px-4 py-3">Plan</th>
                            <th class="px-4 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($campeonatos as $c)
                            <tr class="border-t border-gray-100 align-top">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ $c->nombre }}</div>
                                    <div class="text-xs text-gray-400">{{ $c->publica ? 'Público' : 'Privado' }}{{ $c->activa ? '' : ' · suspendido' }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $c->owner?->name ?? '—' }}<div class="text-xs text-gray-400">{{ $c->owner?->email }}</div></td>
                                <td class="px-4 py-3 text-center">{{ $c->equipos_count }}</td>
                                <td class="px-4 py-3">
                                    @if ($c->esPro())
                                        <span class="rounded-full bg-cancha-50 px-2 py-0.5 text-xs font-semibold text-cancha-700">Pro</span>
                                        <div class="mt-1 text-xs text-gray-500">hasta {{ $c->pro_hasta->format('d/m/Y') }}</div>
                                    @else
                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600">Gratis</span>
                                        @if ($c->equipos_count >= \App\Models\Liga::EQUIPOS_PLAN_GRATIS)
                                            <div class="mt-1 text-xs text-red-600">en el límite</div>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('admin.campeonatos.pro', $c) }}" class="flex flex-wrap items-center gap-2"
                                          data-confirm="¿Activar Pro por 1 año a {{ $c->nombre }}?" onsubmit="return confirm(this.dataset.confirm)">
                                        @csrf
                                        <input type="number" name="monto" min="0" value="{{ $precio }}" title="Monto recibido (0 = cortesía)"
                                               class="w-28 rounded-md border-gray-300 py-1 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <input type="text" name="nota" maxlength="255" placeholder="Nota (ej. transferencia)"
                                               class="w-44 rounded-md border-gray-300 py-1 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <button type="submit" class="rounded-md bg-cancha-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-cancha-700">
                                            {{ $c->esPro() ? '+1 año' : 'Activar Pro' }}
                                        </button>
                                    </form>
                                    @if ($c->esPro())
                                        <form method="POST" action="{{ route('admin.campeonatos.pro.quitar', $c) }}" class="mt-2"
                                              data-confirm="¿Quitar el Pro a {{ $c->nombre }}? Vuelve al plan Gratis de inmediato." onsubmit="return confirm(this.dataset.confirm)">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-red-600 hover:text-red-800">Quitar Pro</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
