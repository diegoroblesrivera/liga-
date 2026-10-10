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

<div>
    <x-input-label for="cupo" value="Cupo de jugadores" />
    <x-text-input id="cupo" name="cupo" type="number" min="2" max="50" class="mt-1 block w-full sm:w-40"
                  :value="old('cupo', $pichanga->cupo ?? 14)" required />
    <p class="mt-1 text-xs text-gray-500">Los que se anoten después quedan en lista de espera.</p>
    <x-input-error :messages="$errors->get('cupo')" class="mt-2" />
</div>

<div class="space-y-4 rounded-md border border-gray-200 p-4"
     x-data="{
        rival: @js((bool) old('tiene_rival', $pichanga->tiene_rival ?? false)),
        modo: @js(old('pago_modo', $pichanga->pago_modo ?? 'completa')),
        precio: @js((string) old('precio', $pichanga->precio ?? '')),
        get total() {
            const p = parseInt(this.precio, 10);
            if (!p || p < 1) return null;
            return this.modo === 'mitad' ? Math.ceil(p / 2) : p;
        },
        pesos(n) { return '$' + n.toLocaleString('es-CL'); },
     }">

    {{-- Equipo rival --}}
    <div>
        <input type="hidden" name="tiene_rival" value="0">
        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
            <input type="checkbox" name="tiene_rival" value="1" x-model="rival"
                   @change="if (rival) modo = 'mitad'"
                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
            Existe equipo rival
        </label>
        <p class="mt-1 text-xs text-gray-500">Márcalo si juegan contra otro grupo en vez de armar dos equipos entre ustedes.</p>

        <div x-show="rival" x-cloak class="mt-3">
            <x-input-label for="rival_nombre" value="Nombre del rival (opcional)" />
            <x-text-input id="rival_nombre" name="rival_nombre" type="text" maxlength="120" class="mt-1 block w-full"
                          placeholder="Los del jueves" :value="old('rival_nombre', $pichanga->rival_nombre ?? '')" />
            <x-input-error :messages="$errors->get('rival_nombre')" class="mt-2" />
        </div>
    </div>

    {{-- Pago --}}
    <div class="border-t border-gray-100 pt-4">
        <x-input-label for="precio" value="Valor de la cancha completa (opcional)" />
        <x-text-input id="precio" name="precio" type="number" min="1" step="1" class="mt-1 block w-full sm:w-48"
                      x-model="precio" :value="old('precio', $pichanga->precio ?? '')" />
        <x-input-error :messages="$errors->get('precio')" class="mt-2" />

        <div class="mt-3 space-y-2">
            <span class="block text-sm font-medium text-gray-700">El grupo paga</span>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="radio" name="pago_modo" value="completa" x-model="modo"
                       class="border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                La cancha completa
            </label>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="radio" name="pago_modo" value="mitad" x-model="modo"
                       class="border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                La mitad de la cancha <span class="text-gray-400">(la otra mitad la paga el rival)</span>
            </label>
            <x-input-error :messages="$errors->get('pago_modo')" class="mt-2" />
        </div>

        <p class="mt-3 rounded-md bg-gray-50 px-3 py-2 text-sm text-gray-700" x-show="total">
            Total a pagar: <strong x-text="total ? pesos(total) : ''"></strong>,
            dividido entre los confirmados de cada fecha.
        </p>

        <div class="mt-4">
            <x-input-label for="datos_transferencia" value="Datos para transferir (opcional)" />
            <textarea id="datos_transferencia" name="datos_transferencia" rows="4" maxlength="500"
                      placeholder="Nombre&#10;RUT&#10;Banco y tipo de cuenta&#10;N° de cuenta&#10;Correo"
                      class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('datos_transferencia', $pichanga->datos_transferencia ?? '') }}</textarea>
            <p class="mt-1 text-xs text-gray-500">Los confirmados los ven junto a su monto para transferir y subir el comprobante.</p>
            <x-input-error :messages="$errors->get('datos_transferencia')" class="mt-2" />
        </div>
    </div>
</div>

<div>
    <x-input-label for="descripcion" value="Descripción (opcional)" />
    <textarea id="descripcion" name="descripcion" rows="2" maxlength="255"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('descripcion', $pichanga->descripcion ?? '') }}</textarea>
    <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
</div>
