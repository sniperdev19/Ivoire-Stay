/* ============================================================
   Afristay — Service Worker
   Scope : dossier où se trouve ce fichier (racine /public de l'app).
   Stratégie : network-first (toujours frais si en ligne), repli cache hors-ligne.
   N'intercepte que les GET same-origin ; les API/POST passent direct au réseau.
   ============================================================ */
const CACHE = 'afristay-v6';

// URLs relatives au scope du SW (= racine de l'app)
const SHELL = [
  'login',
  'manifest.webmanifest',
  'assets/css/saas.css',
  'assets/css/vitrine.css',
  'assets/js/saas.js',
  'assets/js/pwa.js',
  'assets/js/pages/login.js',
  'assets/icons/icon-192.png',
  'assets/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil((async () => {
    const cache = await caches.open(CACHE);
    // Tolérant : un asset manquant n'empêche pas l'installation
    await Promise.allSettled(SHELL.map((u) => cache.add(new Request(u, { cache: 'reload' }))));
    self.skipWaiting();
  })());
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    const keys = await caches.keys();
    await Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)));
    await self.clients.claim();
  })());
});

// ── Notifications push (hors application) ─────────────────────────────────
self.addEventListener('push', (event) => {
  let payload = { title: 'Afristay', body: '' };
  try { if (event.data) payload = event.data.json(); } catch (e) { /* payload non-JSON, on garde le défaut */ }

  const title = payload.title || 'Afristay';
  const options = {
    body: payload.body || '',
    icon: 'assets/icons/icon-192.png',
    badge: 'assets/icons/icon-192.png',
    data: payload.data || {},
    tag: payload.data?.type || undefined, // évite l'empilement de notifs du même type
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const data = event.notification.data || {};
  // Notification voyageur (sans compte SaaS) : pas d'espace "mes réservations",
  // on ramène simplement à la vitrine plutôt que vers l'espace hôtelier.
  const target = data.audience === 'guest'
    ? ''
    : (data.booking_id ? 'saas/bookings' : (data.invoice_id ? 'saas/payments' : 'saas'));
  const url = new URL(target, self.registration.scope).href;

  event.waitUntil((async () => {
    const allClients = await clients.matchAll({ type: 'window', includeUncontrolled: true });
    for (const client of allClients) {
      if (client.url.startsWith(self.registration.scope) && 'focus' in client) {
        client.navigate(url);
        return client.focus();
      }
    }
    return clients.openWindow(url);
  })());
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;                       // ne pas toucher aux POST/PUT (API)
  if (new URL(req.url).origin !== self.location.origin) return; // pas de cross-origin (CDN, Google Fonts)

  event.respondWith((async () => {
    try {
      const fresh = await fetch(req);
      // Met en cache les réponses OK (hors API JSON pour rester frais)
      if (fresh && fresh.ok && !req.url.includes('/api/')) {
        const cache = await caches.open(CACHE);
        cache.put(req, fresh.clone());
      }
      return fresh;
    } catch (e) {
      const cached = await caches.match(req);
      if (cached) return cached;
      // Repli navigation : la page login en cache
      if (req.mode === 'navigate') {
        const fallback = await caches.match('login');
        if (fallback) return fallback;
      }
      throw e;
    }
  })());
});
