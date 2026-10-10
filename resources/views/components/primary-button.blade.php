<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-cancha-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-cancha-700 focus:bg-cancha-700 active:bg-cancha-800 focus:outline-none focus:ring-2 focus:ring-cancha-400 focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
