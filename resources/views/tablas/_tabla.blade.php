{{-- Tabla de posiciones. Recibe: $filas y opcional $clasifican (se destacan los que pasan) --}}
@once
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
@endonce
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
                                @forelse ($filas as $i => $fila)
                                    <tr @if ($i < ($clasifican ?? 0)) style="background:#EEF8F0" @endif>
                                        <td class="fija c1 l pos" @if ($i < ($clasifican ?? 0)) style="background:#EEF8F0;color:#2e7d32;font-weight:600" @endif>{{ $i + 1 }}</td>
                                        <td class="fija c2 l eq" @if ($i < ($clasifican ?? 0)) style="background:#EEF8F0" @endif>{{ $fila->equipo }}</td>
                                        <td class="fija c3 c" @if ($i < ($clasifican ?? 0)) style="background:#EEF8F0" @endif>{{ $fila->pj }}</td>
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

