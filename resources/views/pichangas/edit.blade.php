<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Editar pichanga</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('pichangas.update', $pichanga) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    @include('pichangas._form')

                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="activa" value="1" @checked(old('activa', $pichanga->activa))
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        Activa (desmarca para pausarla sin borrar el historial)
                    </label>

                    <p class="text-xs text-gray-500">
                        Los cambios de día y hora se aplican a las fechas nuevas; la próxima fecha ya creada mantiene su día.
                    </p>

                    <div class="flex items-center justify-end gap-4 pt-2">
                        <a href="{{ route('pichangas.show', $pichanga) }}" class="text-sm text-gray-600 hover:text-gray-900">Cancelar</a>
                        <x-primary-button>Guardar cambios</x-primary-button>
                    </div>
                </form>
            </div>

            {{-- Zona de peligro --}}
            <div class="mt-6 rounded-lg border border-red-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-red-700">Borrar pichanga</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Elimina para siempre todas sus fechas, la asistencia, los invitados, los pagos y los comprobantes subidos.
                    <strong>No se puede recuperar.</strong> Si solo quieres hacer una pausa, desmarca "Activa" arriba.
                </p>
                <div class="mt-4">
                    @include('pichangas._borrar', ['clases' => 'rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700'])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
