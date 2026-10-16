{{-- Pie de página con forma de cancha: césped, líneas de cal, áreas y arcos a cada lado
     y el círculo central. Hecho con HTML/CSS (no imagen) para que el círculo siga redondo
     en cualquier ancho de pantalla. --}}
<footer class="franjas-cesped relative mt-12 overflow-hidden text-white">
    {{-- Líneas de la cancha (decorativas) --}}
    <div class="pointer-events-none absolute inset-x-5 inset-y-4 border-2 border-white/70 sm:inset-x-8" aria-hidden="true">
        {{-- Línea central (el círculo central va con la marca, más abajo) --}}
        <div class="absolute inset-y-0 left-1/2 border-l-2 border-white/70"></div>

        {{-- Lado izquierdo: área grande, área chica, punto penal y arco --}}
        <div class="absolute left-0 top-1/2 h-[64%] w-14 -translate-y-1/2 border-2 border-l-0 border-white/70 sm:w-24"></div>
        <div class="absolute left-0 top-1/2 h-[32%] w-6 -translate-y-1/2 border-2 border-l-0 border-white/70 sm:w-10"></div>
        <div class="absolute left-10 top-1/2 h-1.5 w-1.5 -translate-y-1/2 rounded-full bg-white/80 sm:left-16"></div>
        <div class="absolute -left-[0.95rem] top-1/2 h-[18%] w-3.5 -translate-y-1/2 border-2 border-r-0 border-white sm:-left-[1.45rem] sm:w-5"></div>

        {{-- Lado derecho (espejo) --}}
        <div class="absolute right-0 top-1/2 h-[64%] w-14 -translate-y-1/2 border-2 border-r-0 border-white/70 sm:w-24"></div>
        <div class="absolute right-0 top-1/2 h-[32%] w-6 -translate-y-1/2 border-2 border-r-0 border-white/70 sm:w-10"></div>
        <div class="absolute right-10 top-1/2 h-1.5 w-1.5 -translate-y-1/2 rounded-full bg-white/80 sm:right-16"></div>
        <div class="absolute -right-[0.95rem] top-1/2 h-[18%] w-3.5 -translate-y-1/2 border-2 border-l-0 border-white sm:-right-[1.45rem] sm:w-5"></div>
    </div>

    {{-- Contenido sobre la cancha --}}
    <div class="texto-sobre-video relative mx-auto grid min-h-[17rem] max-w-5xl grid-cols-2 items-center gap-x-4 px-10 py-10 sm:grid-cols-3 sm:px-32">
        {{-- Mitad izquierda: pichangas --}}
        <nav class="text-sm" aria-label="Pichangas">
            <h3 class="titulo-deportivo text-base">Pichangas</h3>
            <ul class="mt-2 space-y-1 text-white/90">
                <li><a href="{{ route('pichangas.index') }}" class="hover:text-white hover:underline">Mis pichangas</a></li>
                <li><a href="{{ route('pichangas.create') }}" class="hover:text-white hover:underline">Crear pichanga</a></li>
            </ul>
        </nav>

        {{-- Círculo central con la marca. El círculo acompaña a la marca (en celular queda
             arriba) y su fondo tapa la línea central para que el texto se lea limpio. --}}
        <div class="order-first col-span-2 mb-8 flex justify-center sm:order-none sm:col-span-1 sm:mb-0">
            <div class="flex h-36 w-36 flex-col items-center justify-center rounded-full border-2 border-white/70 bg-balon/60 text-center sm:h-40 sm:w-40">
                <a href="{{ route('home') }}" aria-label="{{ config('app.name') }}"><x-logo apilado pelota="h-8 w-8" class="text-lg sm:text-xl" /></a>
                <p class="marcador mt-1 text-xl text-white/80">© {{ now()->year }}</p>
                <a href="{{ route('privacidad') }}" class="mt-1 text-xs text-white/80 hover:text-white hover:underline">Privacidad</a>
            </div>
        </div>

        {{-- Mitad derecha: campeonatos --}}
        <nav class="text-right text-sm" aria-label="Campeonatos">
            <h3 class="titulo-deportivo text-base">Campeonatos</h3>
            <ul class="mt-2 space-y-1 text-white/90">
                <li><a href="{{ route('home') }}#campeonatos" class="hover:text-white hover:underline">Campeonatos públicos</a></li>
                <li><a href="{{ route('campeonatos.create') }}" class="hover:text-white hover:underline">Crear campeonato</a></li>
            </ul>
        </nav>
    </div>
</footer>
