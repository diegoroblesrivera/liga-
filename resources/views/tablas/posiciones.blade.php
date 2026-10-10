<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Tabla de posiciones</h2>
    </x-slot>

    <style>
        .tp-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .tp { table-layout: fixed; border-collapse: separate; border-spacing: 0; min-width: 576px; width: 100%; font-size: .875rem; }
        .tp th, .tp td { box-sizing: border-box; padding: .6rem .5rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .tp thead th { background: #f9fafb; color: #6b7280; font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; border-bottom: 1px solid #e5e7eb; }
        .tp tbody td { border-bottom: 1px solid #f3f4f6; color: #374151; }
        .tp tbody tr:last-child td { border-bottom: 0; }
        .tp .l { text-align: left; }
        .tp .c { text-align: center; }
        /* columnas fijas (posición, equipo, PJ, PG) */
        .tp .fija { position: sticky; background: #fff; z-index: 1; }
        .tp thead .fija { background: #f9fafb; z-index: 2; }
        .tp .c1 { left: 0; }
        .tp .c2 { left: 40px; }
        .tp .c3 { left: 180px; box-shadow: 6px 0 6px -4px rgba(0, 0, 0, .12);}
        .tp .c4 { left: 228px;  }
        .tp .eq { font-weight: 500; color: #111827; }
        .tp .pos { color: #9ca3af; }
        .tp .pts { font-weight: 600; color: #111827; }
    </style>

    <div class="py-8">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">

            @include('tablas._selector')

            @if (! $torneo)
                <div class="bg-white p-6 text-sm text-gray-500 shadow-sm sm:rounded-lg">
                    No hay torneos todavía.
                </div>
            @else
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="tp-wrap">
                        <table class="tp">
                            <colgroup>
                                <col style="width:40px">
                                <col style="width:140px">
                                <col style="width:48px">
                                <col style="width:48px">
                                <col style="width:48px">
                                <col style="width:48px">
                                <col style="width:48px">
                                <col style="width:48px">
                                <col style="width:52px">
                                <col style="width:56px">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="fija c1 l">#</th>
                                    <th class="fija c2 l">Equipo</th>
                                    <th class="fija c3 c">PJ</th>
                                    <th class="c">PG</th>
                                    <th class="c">PE</th>
                                    <th class="c">PP</th>
                                    <th class="c">GF</th>
                                    <th class="c">GC</th>
                                    <th class="c">Dif</th>
                                    <th class="c">Pts</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($posiciones as $i => $fila)
                                    <tr>
                                        <td class="fija c1 l pos">{{ $i + 1 }}</td>
                                        <td class="fija c2 l eq">{{ $fila->equipo }}</td>
                                        <td class="fija c3 c">{{ $fila->pj }}</td>
                                        <td class="c">{{ $fila->pg }}</td>
                                        <td class="c">{{ $fila->pe }}</td>
                                        <td class="c">{{ $fila->pp }}</td>
                                        <td class="c">{{ $fila->gf }}</td>
                                        <td class="c">{{ $fila->gc }}</td>
                                        <td class="c">{{ $fila->dif }}</td>
                                        <td class="c pts">{{ $fila->pts }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="c" style="color:#6b7280; padding:1.5rem;">
                                            Aún no hay partidos finalizados en este torneo.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <p class="mt-3 text-xs text-gray-400">
                    PJ jugados · PG ganados · PE empatados · PP perdidos · GF goles a favor · GC en contra · Dif diferencia · Pts puntos.
                    Desliza la tabla hacia la derecha para ver todas las columnas.
                </p>
            @endif

        </div>
    </div>
</x-app-layout>
