// Service worker de TuTorneo: hace la app instalable y muestra una página propia sin conexión.
// A propósito NO guarda páginas en caché: tienen datos personales (pagos, RUN, comprobantes)
// y siempre deben venir frescas del servidor. Solo se guardan estilos, íconos y la página offline.
const VERSION = 'tutorneo-v1';
const OFFLINE = '/offline.html';
const PRECARGA = [OFFLINE, '/icons/icono-192.png', '/icons/icono-512.png', '/manifest.webmanifest'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(VERSION).then((cache) => cache.addAll(PRECARGA)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((claves) => Promise.all(claves.filter((c) => c !== VERSION).map((c) => caches.delete(c))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;

    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return;

    // Páginas: siempre de la red; sin conexión, la página offline.
    if (req.mode === 'navigate') {
        event.respondWith(fetch(req).catch(() => caches.match(OFFLINE)));
        return;
    }

    // Estilos y scripts compilados (llevan hash en el nombre) e íconos: caché primero.
    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/')) {
        event.respondWith(
            caches.match(req).then((guardado) => guardado || fetch(req).then((resp) => {
                if (resp.ok) {
                    const copia = resp.clone();
                    caches.open(VERSION).then((cache) => cache.put(req, copia));
                }
                return resp;
            }))
        );
    }
});
