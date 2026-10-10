@php($pichanga = $pichanga ?? null)

<div>
    <x-input-label for="nombre" value="Nombre" />
    <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" placeholder="Pichanga de los martes"
                  :value="old('nombre', $pichanga->nombre ?? '')" required autofocus />
    <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
</div>

<div>
    <x-input-label for="lugar" value="Lugar / cancha (opcional)" />
    <x-text-input id="lugar" name="lugar" type="text" class="mt-1 block w-full"
                  :value="old('lugar', $pichanga->lugar ?? '')" />
    <x-input-error :messages="$errors->get('lugar')" class="mt-2" />
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div>
        <x-input-label for="dia_semana" value="Día" />
        <select id="dia_semana" name="dia_semana" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach (\App\Models\Pichanga::DIAS as $valor => $dia)
                <option value="{{ $valor }}" @selected((string) old('dia_semana', $pichanga->dia_semana ?? '') === (string) $valor)>{{ $dia }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('dia_semana')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="hora" value="Hora" />
        <x-text-input id="hora" name="hora" type="time" class="mt-1 block w-full"
                      :value="old('hora', $pichanga?->horaCorta() ?? '21:00')" required />
        <x-input-error :messages="$errors->get('hora')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="duracion_min" value="Duración (min)" />
        <x-text-input id="duracion_min" name="duracion_min" type="number" min="30" max="240" step="15" class="mt-1 block w-full"
                      :value="old('duracion_min', $pichanga->duracion_min ?? 60)" required />
        <x-input-error :messages="$errors->get('duracion_min')" class="mt-2" />
    </div>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <x-input-label for="cupo" value="Cupo de jugadores" />
        <x-text-input id="cupo" name="cupo" type="number" min="2" max="50" class="mt-1 block w-full"
                      :value="old('cupo', $pichanga->cupo ?? 14)" required />
        <p class="mt-1 text-xs text-gray-500">Los que se anoten después quedan en lista de espera.</p>
        <x-input-error :messages="$errors->get('cupo')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="precio" value="Valor total de la cancha (opcional)" />
        <x-text-input id="precio" name="precio" type="number" min="1" step="1" class="mt-1 block w-full"
                      :value="old('precio', $pichanga->precio ?? '')" />
        <p class="mt-1 text-xs text-gray-500">Se divide entre los confirmados.</p>
        <x-input-error :messages="$errors->get('precio')" class="mt-2" />
    </div>
</div>

<div>
    <x-input-label for="descripcion" value="Descripción (opcional)" />
    <textarea id="descripcion" name="descripcion" rows="2" maxlength="255"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('descripcion', $pichanga->descripcion ?? '') }}</textarea>
    <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
</div>
