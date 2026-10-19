{{-- Lista de un equipo en un partido. Recibe: $partido, $equipo, $asistencias (keyBy jugador_id), $yo --}}
@php
    $jugadoresEquipo = $equipo->jugadores->sortBy(fn ($j) => $j->dorsal ?? 999);
    $presentesEquipo = $jugadoresEquipo->filter(fn ($j) => $asistencias->has($j->id))->count();
@endphp
<div>
    <div class="mb-2 flex items-baseline justify-between">
        <h4 class="text-sm font-semibold text-gray-900">{{ $equipo->nombre }}</h4>
        <span class="text-xs text-gray-500">{{ $presentesEquipo }}/{{ $jugadoresEquipo->count() }} presentes</span>
    </div>
    <ul class="divide-y divide-gray-100 rounded-md border border-gray-100">
        @forelse ($jugadoresEquipo as $j)
            @php
                $a = $asistencias->get($j->id);
            @endphp
            <li class="flex items-center justify-between gap-2 px-3 py-2 text-sm">
                <span class="{{ $a ? 'text-gray-900' : 'text-gray-400' }}">
                    {{ $a ? '✅' : '⬜' }}
                    {{ $j->dorsal ? '#'.$j->dorsal.' ' : '' }}{{ $j->nombre }}
                    @if ($a)
                        <span class="text-xs text-gray-400">{{ $a->created_at->format('H:i') }}{{ $a->metodo === 'manual' ? ' · a mano' : '' }}</span>
                    @endif
                </span>
                @if (\App\Support\Lista::puedeMarcar($yo, $partido, $j))
                    @if ($a)
                        <form method="POST" action="{{ route('lista.desmarcar', [$partido, $j]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:text-red-800">Quitar</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('lista.marcar', $partido) }}">
                            @csrf
                            <input type="hidden" name="jugador_id" value="{{ $j->id }}">
                            <button type="submit" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Marcar</button>
                        </form>
                    @endif
                @endif
            </li>
        @empty
            <li class="px-3 py-2 text-sm text-gray-400">Sin jugadores.</li>
        @endforelse
    </ul>
</div>
