@props(['texto' => null, 'etiqueta' => 'WhatsApp'])

{{-- Abre WhatsApp (app o web) con el mensaje listo para elegir a quién mandarlo.
     Con $texto, el mensaje va fijo; sin él, el botón se arma desde JS con :href. --}}
<a {{ $attributes->merge([
        'href' => $texto !== null ? 'https://wa.me/?text='.rawurlencode($texto) : '#',
        'target' => '_blank',
        'rel' => 'noopener',
        'class' => 'inline-flex shrink-0 items-center justify-center gap-1.5 rounded-md px-3 py-2 text-sm font-medium text-white hover:opacity-90',
        'style' => 'background:#25D366',
    ]) }}>
    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M17.5 14.4c-.3-.1-1.7-.8-2-.9-.3-.1-.5-.1-.7.1-.2.3-.8.9-.9 1.1-.2.2-.3.2-.6.1-.3-.1-1.2-.4-2.3-1.4-.9-.8-1.4-1.7-1.6-2-.2-.3 0-.5.1-.6l.4-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.4s1 2.8 1.2 3c.1.2 2 3.1 4.9 4.3.7.3 1.2.5 1.6.6.7.2 1.3.2 1.8.1.6-.1 1.7-.7 1.9-1.4.2-.7.2-1.2.2-1.4-.1-.1-.3-.2-.6-.3zM12 2C6.5 2 2 6.5 2 12c0 1.8.5 3.5 1.3 4.9L2 22l5.2-1.3c1.4.8 3.1 1.2 4.8 1.2 5.5 0 10-4.5 10-10S17.5 2 12 2zm0 18.2c-1.6 0-3.1-.4-4.4-1.2l-.3-.2-3.1.8.8-3-.2-.3C4 15 3.6 13.5 3.6 12c0-4.6 3.8-8.4 8.4-8.4s8.4 3.8 8.4 8.4-3.8 8.2-8.4 8.2z"/>
    </svg>
    {{ $etiqueta }}
</a>
