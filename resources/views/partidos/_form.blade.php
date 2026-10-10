@php($p = $partido ?? null)

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

    <div class="sm:col-span-2">
        <x-input-label for="torneo_id" value="Torneo" />
        <select id="torneo_id" name="torneo_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Selecciona…</option>
            @foreach ($torneos as $t)
                <option value="{{ $t->id }}" @selected(old('torneo_id', $p?->torneo_id) == $t->id)>{{ $t->nombre }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('torneo_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="numero_fecha" value="N° de fecha (jornada)" />
        <x-text-input id="numero_fecha" name="numero_fecha" type="number" min="1" class="mt-1 block w-full"
                      :value="old('numero_fecha', $p?->numero_fecha)" required />
        <x-input-error :messages="$errors->get('numero_fecha')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="fecha_hora" value="Fecha y hora" />
        <x-text-input id="fecha_hora" name="fecha_hora" type="datetime-local" class="mt-1 block w-full"
                      :value="old('fecha_hora', optional($p?->fecha_hora)->format('Y-m-d\TH:i'))" />
        <x-input-error :messages="$errors->get('fecha_hora')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="equipo_local_id" value="Local" />
        <select id="equipo_local_id" name="equipo_local_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Selecciona…</option>
            @foreach ($equipos as $e)
                <option value="{{ $e->id }}" @selected(old('equipo_local_id', $p?->equipo_local_id) == $e->id)>{{ $e->nombre }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('equipo_local_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="equipo_visita_id" value="Visita" />
        <select id="equipo_visita_id" name="equipo_visita_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Selecciona…</option>
            @foreach ($equipos as $e)
                <option value="{{ $e->id }}" @selected(old('equipo_visita_id', $p?->equipo_visita_id) == $e->id)>{{ $e->nombre }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('equipo_visita_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="cancha_id" value="Cancha (opcional)" />
        <select id="cancha_id" name="cancha_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Sin definir</option>
            @foreach ($canchas as $c)
                <option value="{{ $c->id }}" @selected(old('cancha_id', $p?->cancha_id) == $c->id)>{{ $c->nombre }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('cancha_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="arbitro_id" value="Árbitro (opcional)" />
        <select id="arbitro_id" name="arbitro_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Sin asignar</option>
            @foreach ($arbitros as $a)
                <option value="{{ $a->id }}" @selected(old('arbitro_id', $p?->arbitro_id) == $a->id)>{{ $a->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('arbitro_id')" class="mt-2" />
    </div>

</div>
