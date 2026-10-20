<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">📥 Cargar equipos desde Excel</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl space-y-4 sm:px-6 lg:px-8">
            @include('carga-masiva._errores')

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-base font-medium text-gray-900">1. Descarga la plantilla</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Una fila por equipo con: <strong>Equipo</strong>, <strong>Nombre DT</strong>, <strong>Email DT</strong> y, si quieres, <strong>Teléfono DT</strong>.
                </p>
                <a href="{{ route('carga.equipos.plantilla') }}"
                   class="mt-3 inline-flex rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">⬇️ Descargar plantilla (.xlsx)</a>
            </div>

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-base font-medium text-gray-900">2. Sube el archivo completo</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Se crean los equipos y cada DT recibe un correo para completar su inscripción. Después él carga su plantel,
                    el color de la camiseta, el escudo y la foto del equipo.
                </p>
                @if ($limite !== null)
                    <p class="mt-2 text-xs text-gray-500">Plan Gratis: tienes {{ $equipos }} de {{ $limite }} equipos.</p>
                @endif

                <form method="POST" action="{{ route('carga.equipos.importar') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="modalidad_id" value="Modalidad de los equipos" />
                        <select id="modalidad_id" name="modalidad_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($modalidades as $m)
                                <option value="{{ $m->id }}" @selected(old('modalidad_id') == $m->id)>{{ $m->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="archivo" value="Archivo (.xlsx o .csv)" />
                        <input id="archivo" name="archivo" type="file" accept=".xlsx,.xls,.csv" required
                               class="mt-1 block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-indigo-700">
                        <x-input-error :messages="$errors->get('modalidad_id')" class="mt-2" />
                    </div>
                    <div class="flex items-center justify-end gap-4">
                        <a href="{{ route('equipos.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancelar</a>
                        <x-primary-button>Cargar equipos</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
