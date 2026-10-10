@php
    $vacio = ! $jug;
    $fill = $vacio ? '#9ca3af' : ($color ?: '#374151');
    $nombre = $vacio ? '' : \Illuminate\Support\Str::of($jug->nombre)->trim()->explode(' ')->first();
@endphp
<div class="lu-jug">
    <div class="lu-shirt">
        <svg viewBox="0 0 100 90" xmlns="http://www.w3.org/2000/svg">
            <polygon points="30,8 40,8 50,16 60,8 70,8 92,26 78,44 70,38 70,84 30,84 30,38 22,44 8,26"
                     fill="{{ $fill }}" stroke="rgba(0,0,0,0.28)" stroke-width="2" stroke-linejoin="round" />
        </svg>
    </div>
    <div class="lu-nombre">{{ $nombre !== '' ? $nombre : '—' }}</div>
</div>
