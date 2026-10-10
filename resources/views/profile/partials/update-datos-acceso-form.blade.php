@php
    $filas = old('acompanantes', $user->acompanantes->map(fn ($a) => [
        'nombre' => $a->nombre,
        'run' => \App\Support\Run::formatear($a->run),
    ])->all());
@endphp

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Datos para el acceso</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Algunos recintos piden la lista de quienes entran. Estos datos solo los ve el organizador
            de las pichangas en las que confirmas asistencia.
        </p>
    </header>

    <form method="post" action="{{ route('profile.acceso.update') }}" class="mt-6 space-y-6"
          x-data="{ acompanantes: @js(array_values($filas)) }">
        @csrf
        @method('put')

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="run" value="RUN" />
                <x-text-input id="run" name="run" type="text" class="mt-1 block w-full" placeholder="12.345.678-5"
                              :value="old('run', \App\Support\Run::formatear($user->run))" />
                <x-input-error class="mt-2" :messages="$errors->datosAcceso->get('run')" />
            </div>

            <div>
                <x-input-label for="patente" value="Patente del vehículo (opcional)" />
                <x-text-input id="patente" name="patente" type="text" class="mt-1 block w-full uppercase" placeholder="BBCL12"
                              :value="old('patente', $user->patente)" />
                <x-input-error class="mt-2" :messages="$errors->datosAcceso->get('patente')" />
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Acompañantes</h3>
                <button type="button" @click="acompanantes.push({ nombre: '', run: '' })"
                        x-show="acompanantes.length < 10"
                        class="text-sm font-medium text-indigo-600 hover:text-indigo-800">+ Agregar acompañante</button>
            </div>

            <p class="mt-1 text-sm text-gray-500" x-show="acompanantes.length === 0">
                No tienes acompañantes registrados.
            </p>

            <div class="mt-3 space-y-3">
                <template x-for="(a, i) in acompanantes" :key="i">
                    <div class="flex items-start gap-2">
                        <input type="text" :name="`acompanantes[${i}][nombre]`" x-model="a.nombre" placeholder="Nombre" maxlength="120"
                               class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <input type="text" :name="`acompanantes[${i}][run]`" x-model="a.run" placeholder="RUN"
                               class="block w-40 shrink-0 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <button type="button" @click="acompanantes.splice(i, 1)" title="Quitar"
                                class="shrink-0 rounded-md px-2 py-2 text-sm text-red-600 hover:bg-red-50">✕</button>
                    </div>
                </template>
            </div>

            @foreach ($errors->datosAcceso->get('acompanantes*') as $mensajes)
                <x-input-error class="mt-2" :messages="$mensajes" />
            @endforeach
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Guardar</x-primary-button>

            @if (session('status') === 'acceso-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                   class="text-sm text-gray-600 dark:text-gray-400">Guardado.</p>
            @endif
        </div>
    </form>
</section>
