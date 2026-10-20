<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">📥 Cargar jugadores · {{ $equipo->nombre }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl space-y-4 sm:px-6 lg:px-8">
            @include('carga-masiva._errores')

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-base font-medium text-gray-900">1. Descarga la plantilla</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Una fila por jugador con: <strong>Nombre</strong>, <strong>Dorsal</strong> (número de camiseta), <strong>Posición</strong> y <strong>Email</strong>.
                    Solo el nombre es obligatorio.
                </p>
                <a href="{{ route('carga.jugadores.plantilla', $equipo) }}"
                   class="mt-3 inline-flex rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">⬇️ Descargar plantilla (.xlsx)</a>
            </div>

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-base font-medium text-gray-900">2. Sube el archivo completo</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Los jugadores quedan en el plantel de inmediato. Los que tienen email reciben un correo para completar su inscripción
                    y subir su foto; los demás puedes invitarlos después con el link de jugadores.
                </p>
                @if ($cupo !== null)
                    <p class="mt-2 text-xs text-gray-500">Plantel: {{ $jugadores }} de {{ $cupo }} jugadores ({{ $equipo->modalidad->nombre }}).</p>
                @endif

                <form method="POST" action="{{ route('carga.jugadores.importar', $equipo) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="archivo" value="Archivo (.xlsx o .csv)" />
                        <input id="archivo" name="archivo" type="file" accept=".xlsx,.xls,.csv" required
                               class="mt-1 block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-indigo-700">
                    </div>
                    <div class="flex items-center justify-end gap-4">
                        <a href="{{ route('equipos.show', $equipo) }}" class="text-sm text-gray-600 hover:text-gray-900">Cancelar</a>
                        <x-primary-button>Cargar jugadores</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
