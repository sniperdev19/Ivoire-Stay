/* ============================================================
   Afristay — Service Worker
   Scope : dossier où se trouve ce fichier (racine /public de l'app).
   Stratégie : network-first (toujours frais si en ligne), repli cache hors-ligne.
   N'intercepte que les GET same-origin ; les API/POST passent direct au réseau.
   ============================================================ */
const CACHE = 'afristay-v8';

// URL absolue de la page login (résolue une fois, sert de comparaison exacte
// dans le fallback navigation ci-dessous — ne pas la servir pour n'importe
// quelle page non mise en cache, sinon un utilisateur déjà connecté qui
// navigue hors ligne vers une page jamais visitée voit le formulaire de
// connexion s'afficher à la place : ça ressemble à une déconnexion
// automatique alors que le token en localStorage n'a pas bougé.
const LOGIN_URL = new URL('login', self.location).href;

// Page neutre servie pour une navigation hors ligne vers une page jamais
// mise en cache (hors /login) — volontairement sans formulaire de connexion.
const OFFLINE_HTML = `<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Hors ligne — Afristay</title>
<style>
body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
  background:#0F2B20;color:#fff;font-family:Arial,Helvetica,sans-serif;text-align:center;padding:24px;}
.box{max-width:360px;}
h1{font-size:20px;margin:0 0 10px;color:#C9A84C;}
p{font-size:14px;line-height:1.5;color:rgba(255,255,255,0.85);margin:0 0 20px;}
button{background:#C9A84C;color:#0F2B20;border:none;border-radius:8px;padding:10px 20px;
  font-weight:700;font-size:14px;cursor:pointer;}
</style></head><body>
<div class="box">
<h1>Vous êtes hors ligne</h1>
<p>Cette page n'a pas encore été chargée pendant que vous étiez connecté. Votre session reste active — reconnectez-vous au réseau puis réessayez.</p>
<button onclick="location.reload()">Réessayer</button>
</div>
</body></html>`;

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
    // Jusqu'à 2 tentatives réseau avant de considérer la requête en échec —
    // absorbe les micro-coupures réseau (bascule d'antenne, wifi ↔ 4G...).
    // Sans ça, une ressource jamais encore mise en cache (typiquement le CSS
    // lors de la toute première visite d'un appareil, cache vide) échouait
    // intégralement au moindre accroc réseau au lieu de simplement réessayer
    // — d'où des pages qui s'affichaient sans style et qu'un rechargement
    // manuel suffisait à corriger (l'utilisateur refaisait à la main ce que
    // cette boucle fait maintenant automatiquement).
    for (let attempt = 1; attempt <= 2; attempt++) {
      try {
        const fresh = await fetch(req);
        // Met en cache les réponses OK (hors API JSON pour rester frais)
        if (fresh && fresh.ok && !req.url.includes('/api/')) {
          const cache = await caches.open(CACHE);
          cache.put(req, fresh.clone());
        }
        return fresh;
      } catch (e) {
        if (attempt === 1) continue; // micro-coupure probable : on retente une fois immédiatement
      }
    }

    // Les deux tentatives réseau ont échoué : repli hors-ligne habituel.
    const cached = await caches.match(req);
    if (cached) return cached;
    if (req.mode === 'navigate') {
      // Repli navigation vers /login précisément : page en cache.
      if (req.url.split('?')[0] === LOGIN_URL) {
        const fallback = await caches.match('login');
        if (fallback) return fallback;
      }
      // Toute autre page jamais mise en cache (ex. première visite hors
      // ligne d'une page SaaS) : ne PAS servir le shell login — un
      // utilisateur déjà connecté croirait avoir été déconnecté. On sert
      // une page neutre invitant à réessayer une fois reconnecté.
      return new Response(OFFLINE_HTML, {
        status: 200,
        headers: { 'Content-Type': 'text/html; charset=utf-8' },
      });
    }
    // Ne jamais rejeter la promesse de respondWith() (Chrome logue alors un
    // "Uncaught (in promise)" bruyant en console pour chaque requête
    // échouée) : on résout avec une Response de type erreur réseau, qui
    // fait échouer le fetch() côté page exactement pareil (TypeError),
    // sans le bruit console côté Service Worker.
    return Response.error();
  })());
});
