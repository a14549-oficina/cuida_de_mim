/*Cuida de Mim — Service Worker
 *Estratégia: Cache-first para assets estáticos, Network-first para páginas PHP*/

const CACHE_NAME = 'cuida-de-mim-v2';
const STATIC_ASSETS = [
  './css/style.css',
  './offline.html',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
  'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js',
];

// Install: cache static assets
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(STATIC_ASSETS))
      .then(() => self.skipWaiting())
  );
});

// Activate: remove old caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

// Fetch: Network-first for PHP, Cache-first for static assets
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // Skip non-GET and cross-origin (except CDN assets handled below)
  if (event.request.method !== 'GET') return;

  // Static assets: cache-first
  if (
    url.pathname.endsWith('.css') ||
    url.pathname.endsWith('.js') ||
    url.pathname.endsWith('.png') ||
    url.pathname.endsWith('.svg') ||
    url.pathname.endsWith('.html') ||
    url.hostname.includes('cdnjs.cloudflare.com') ||
    url.hostname.includes('fonts.googleapis.com') ||
    url.hostname.includes('fonts.gstatic.com')
  ) {
    event.respondWith(
      caches.match(event.request).then(cached => cached || fetch(event.request).then(resp => {
        if (resp.ok) {
          const clone = resp.clone();
          caches.open(CACHE_NAME).then(c => c.put(event.request, clone));
        }
        return resp;
      }))
    );
    return;
  }

  // PHP pages: network-first, fallback to offline page
  event.respondWith(
    fetch(event.request).catch(() =>
      caches.match('./offline.html').then(r => r || new Response('<h1>Sem ligação</h1>', {
        status: 503,
        headers: {'Content-Type': 'text/html; charset=utf-8'}
      }))
    )
  );
});
