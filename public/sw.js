/* Service worker mínimo: cachea solo assets estáticos.
 * El HTML queda fuera del cache a propósito: lleva tokens CSRF y estado
 * de sesión, y una copia vieja rompería formularios y el carrito.
 */
const CACHE = 'jt-estaticos-v1';
const PRECACHE = [
  './img/favicon.svg',
  './img/logo.svg',
  './img/icons.svg',
  './img/apple-touch-icon.png',
  './img/icon-192.png',
  './img/icon-512.png',
  './favicon.ico'
];
const ESTATICOS = /\.(?:css|js|mjs|svg|png|jpe?g|webp|gif|ico|woff2?)$/i;

self.addEventListener('install', (evento) => {
  evento.waitUntil(
    caches.open(CACHE)
      .then((cache) => cache.addAll(PRECACHE))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (evento) => {
  evento.waitUntil(
    caches.keys()
      .then((claves) => Promise.all(
        claves.filter((clave) => clave !== CACHE).map((clave) => caches.delete(clave))
      ))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (evento) => {
  const url = new URL(evento.request.url);
  if (evento.request.method !== 'GET') return;
  if (url.origin !== self.location.origin) return;
  if (url.pathname.endsWith('/sw.js')) return;
  if (!ESTATICOS.test(url.pathname)) return;

  evento.respondWith(
    caches.match(evento.request).then((guardado) => guardado || fetch(evento.request).then((respuesta) => {
      if (respuesta.ok && respuesta.type === 'basic') {
        const copia = respuesta.clone();
        caches.open(CACHE).then((cache) => cache.put(evento.request, copia));
      }
      return respuesta;
    }))
  );
});
