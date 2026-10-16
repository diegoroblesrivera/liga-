

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// App instalable (PWA). Ver public/sw.js: no guarda páginas con datos, solo estilos e íconos.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}
