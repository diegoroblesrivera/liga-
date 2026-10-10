@php($torneo = $torneo ?? null)

<div>
    <x-input-label for="nombre" value="Nombre" />
    <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full"
                  :value="old('nombre', $torneo->nombre ?? '')" required autofocus />
    <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
</div>

<div>
    <x-input-label for="temporada" value="Temporada (opcional)" />
    <x-text-input id="temporada" name="temporada" type="text" class="mt-1 block w-full"
                  placeholder="Ej: 2026, Apertura 2026" :value="old('temporada', $torneo->temporada ?? '')" />
    <x-input-error :messages="$errors->get('temporada')" class="mt-2" />
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <x-input-label for="fecha_inicio" value="Fecha de inicio (opcional)" />
        <x-text-input id="fecha_inicio" name="fecha_inicio" type="date" class="mt-1 block w-full"
                      :value="old('fecha_inicio', optional($torneo->fecha_inicio ?? null)->format('Y-m-d'))" />
        <x-input-error :messages="$errors->get('fecha_inicio')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="fecha_fin" value="Fecha de fin (opcional)" />
        <x-text-input id="fecha_fin" name="fecha_fin" type="date" class="mt-1 block w-full"
                      :value="old('fecha_fin', optional($torneo->fecha_fin ?? null)->format('Y-m-d'))" />
        <x-input-error :messages="$errors->get('fecha_fin')" class="mt-2" />
    </div>
</div>
