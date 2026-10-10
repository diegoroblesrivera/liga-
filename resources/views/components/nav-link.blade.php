@props(['active'])

@php
// Enlaces del menú superior (fondo negro balón).
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-4 border-cancha-400 text-sm font-semibold leading-5 text-white focus:outline-none transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-4 border-transparent text-sm font-medium leading-5 text-gray-300 hover:text-white hover:border-gray-500 focus:outline-none focus:text-white focus:border-gray-500 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
