@php($liga = $liga ?? null)

<div>
    <x-input-label for="nombre" value="Nombre del campeonato" />
    <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" placeholder="Liga del Barrio 2027"
                  :value="old('nombre', $liga->nombre ?? '')" required autofocus maxlength="120" />
    <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
</div>

<div>
    <x-input-label for="descripcion" value="Descripción (opcional)" />
    <textarea id="descripcion" name="descripcion" rows="2" maxlength="255"
              placeholder="Futbolito los sábados en el complejo municipal"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('descripcion', $liga->descripcion ?? '') }}</textarea>
    <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
</div>

<div>
    <input type="hidden" name="publica" value="0">
    <label class="flex items-start gap-2 text-sm text-gray-700">
        <input type="checkbox" name="publica" value="1" @checked(old('publica', $liga->publica ?? true))
               class="mt-0.5 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
        <span>
            <span class="font-medium">Campeonato público</span><br>
            <span class="text-gray-500">Aparece en la portada y cualquiera puede ver la tabla, las fechas y los goleadores (sin datos personales).</span>
        </span>
    </label>
</div>
