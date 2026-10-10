@php($cancha = $cancha ?? null)

<div>
    <x-input-label for="nombre" value="Nombre" />
    <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full"
                  :value="old('nombre', $cancha->nombre ?? '')" required autofocus />
    <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
</div>

<div>
    <x-input-label for="direccion" value="Dirección (opcional)" />
    <x-text-input id="direccion" name="direccion" type="text" class="mt-1 block w-full"
                  :value="old('direccion', $cancha->direccion ?? '')" />
    <x-input-error :messages="$errors->get('direccion')" class="mt-2" />
</div>
