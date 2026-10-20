<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        @if ($invitacion->tipo === 'arbitro')
            Te estás registrando como <strong>Árbitro</strong> de la liga.
        @else
            Te estás uniendo al equipo <strong>{{ $equipo->nombre }}</strong>
            como <strong>{{ $invitacion->tipo === 'dt' ? 'Director Técnico' : 'Jugador' }}</strong>.
        @endif
        @if ($ficha)
            <div class="mt-2 rounded-md bg-gray-50 px-3 py-2 text-gray-700">
                Tu ficha: <strong>{{ $ficha->nombre }}</strong>{{ $ficha->dorsal ? ' · #'.$ficha->dorsal : '' }}{{ $ficha->posicion ? ' · '.$ficha->posicion : '' }}
            </div>
        @endif
    </div>

    @auth
        {{-- Ya tiene cuenta: acepta con su sesión --}}
        <div class="rounded-md border border-gray-200 p-4 text-sm text-gray-700">
            Entraste como <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->email }}).
        </div>
        @error('cupo')
            <p class="mt-4 text-sm font-medium text-red-600">{{ $message }}</p>
        @enderror
        <form method="POST" action="{{ route('invitacion.aceptar', $invitacion->token) }}" class="mt-4">
            @csrf
            <x-primary-button class="w-full justify-center">Aceptar invitación</x-primary-button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="mt-3 text-center">
            @csrf
            <button type="submit" class="text-sm text-gray-500 underline hover:text-gray-700">No soy yo: cerrar sesión</button>
        </form>
    @else
    <x-google-button texto="Continuar con Google" />
    <p class="mt-3 text-center text-sm text-gray-600">
        ¿Ya tienes cuenta? <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-800">Inicia sesión</a> y vuelve a este link.
    </p>
    <div class="my-4 flex items-center gap-3 text-xs text-gray-400"><span class="h-px flex-1 bg-gray-200"></span>o crea tu cuenta<span class="h-px flex-1 bg-gray-200"></span></div>

    <form method="POST" action="{{ route('invitacion.store', $invitacion->token) }}">
        @csrf

        {{-- Nombre --}}
        <div>
            <x-input-label for="name" value="Nombre" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                          :value="old('name', $invitacion->nombre)" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        {{-- Email --}}
        <div class="mt-4">
            <x-input-label for="email" value="Correo" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                          :value="old('email', $invitacion->email)" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        {{-- Contraseña --}}
        <div class="mt-4">
            <x-input-label for="password" value="Contraseña" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                          required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        {{-- Confirmar contraseña --}}
        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Confirmar contraseña" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                          class="mt-1 block w-full" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        {{-- DT: casilla para sumarse como jugador (marcada por defecto) --}}
        @if ($invitacion->tipo === 'dt' && ! $ficha)
            <div class="mt-4">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="jugar" value="1" id="jugar"
                           @checked(old('jugar', true))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                           onchange="document.getElementById('campos-jugador').classList.toggle('hidden', !this.checked)">
                    Agregarme como jugador también
                </label>
            </div>
        @endif

        {{-- Campos de jugador: siempre para jugador; para DT dentro del bloque que se muestra/oculta --}}
        @if (! $ficha && ($invitacion->tipo === 'jugador' || $invitacion->tipo === 'dt'))
            <div id="campos-jugador" @if ($invitacion->tipo === 'dt' && ! old('jugar', true)) class="hidden" @endif>
                <div class="mt-4">
                    <x-input-label for="dorsal" value="Número de camiseta (opcional)" />
                    <x-text-input id="dorsal" name="dorsal" type="number" min="1" max="99"
                                  class="mt-1 block w-full" :value="old('dorsal')" />
                    <x-input-error :messages="$errors->get('dorsal')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="posicion" value="Posición (opcional)" />
                    <x-text-input id="posicion" name="posicion" type="text" class="mt-1 block w-full"
                                  :value="old('posicion')" />
                    <x-input-error :messages="$errors->get('posicion')" class="mt-2" />
                </div>
            </div>
        @endif

        {{-- Error de cupo lleno --}}
        @error('cupo')
            <p class="mt-4 text-sm font-medium text-red-600">{{ $message }}</p>
        @enderror

        <div class="mt-6 flex items-center justify-end">
            <x-primary-button>
                Crear cuenta y unirme
            </x-primary-button>
        </div>
    </form>
    @endauth
</x-guest-layout>
