<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Árbitros</h2>

            <form method="POST" action="{{ route('arbitros.invitar') }}">
                @csrf
                <x-primary-button>Generar link de árbitro</x-primary-button>
            </form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>
            @endif

            {{-- Link recién generado --}}
            @if (session('link_invitacion'))
                <div class="rounded-md bg-indigo-50 p-4">
                    <p class="mb-2 text-sm font-medium text-indigo-800">
                        Link de árbitro generado — cópialo y envíaselo a quien será árbitro:
                    </p>
                    <div class="flex gap-2">
                        <input id="link-invitacion" type="text" readonly
                               value="{{ session('link_invitacion') }}"
                               class="w-full rounded-md border-gray-300 bg-white text-sm text-gray-700">
                        <button type="button" onclick="copiarLink()"
                                class="shrink-0 rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Copiar
                        </button>
                        <x-whatsapp :texto="session('mensaje_invitacion', session('link_invitacion'))" />
                    </div>
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                @forelse ($arbitros as $arbitro)
                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 last:border-0">
                        <span class="font-medium text-gray-900">{{ $arbitro->name }}</span>
                        <span class="text-sm text-gray-500">{{ $arbitro->email }}</span>
                    </div>
                @empty
                    <p class="px-6 py-4 text-sm text-gray-500">
                        Aún no hay árbitros. Genera un link con el botón de arriba y envíaselo a quien será árbitro.
                    </p>
                @endforelse
            </div>

        </div>
    </div>

    <script>
        function copiarLink() {
            const input = document.getElementById('link-invitacion');
            input.select();
            navigator.clipboard.writeText(input.value);
        }
    </script>
</x-app-layout>
