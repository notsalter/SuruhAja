const CACHE = 'suruhaja-v1';
const PRECACHE = [
  '/styles.css',
  '/icon-192.png',
  '/icon-512.png',
  '/apple-touch-icon.png',
  '/manifest.webmanifest',
  '/offline.html'
];

// Endpoints that must always hit the network (stateful / payment data)
const NETWORK_ONLY = ['order_status.php', 'order_payment_callback.php'];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;

  const url = new URL(req.url);
  if (url.origin !== location.origin) return;

  // Never cache polling/payment endpoints
  if (NETWORK_ONLY.some((p) => url.pathname.includes(p))) return;

  // Navigations: network first, fall back to the cached homepage then offline page
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req)
        .then((res) => res)
        .catch(() =>
          caches.match('/index.php').then((hit) => hit || caches.match('/offline.html'))
        )
    );
    return;
  }

  // Static assets: cache first
  if (/\.(css|js|png|jpe?g|gif|svg|webp|woff2?|ico)$/i.test(url.pathname)) {
    event.respondWith(
      caches.match(req).then((hit) => hit || fetch(req).then((res) => {
        const copy = res.clone();
        if (res.ok) caches.open(CACHE).then((cache) => cache.put(req, copy));
        return res;
      }))
    );
  }
});