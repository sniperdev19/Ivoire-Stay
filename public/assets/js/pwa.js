/* ============================================================
   Afristay — PWA : enregistrement du Service Worker,
   détection du mode d'affichage (navigateur vs app installée),
   et gestion de l'invite d'installation (Android / iOS / PC).
   Chargé avant Alpine sur toutes les pages.
   ============================================================ */
(function () {
  // Déduit la base de l'app depuis le <link rel="manifest"> (gère le sous-dossier).
  // Générique sur le nom de fichier (manifest.webmanifest, manifest-agent.webmanifest, …) :
  // on retire juste le dernier segment de chemin, pas un nom en dur.
  function appBase() {
    const m = document.querySelector('link[rel="manifest"]');
    if (m) return m.href.replace(/\/[^\/]*$/, '/');
    return location.href.replace(/\/[^\/]*$/, '/');
  }

  // Convertit la clé publique VAPID (base64url) au format attendu par PushManager.subscribe()
  function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    return Uint8Array.from([...rawData].map((c) => c.charCodeAt(0)));
  }

  // ── Conversions base64url <-> ArrayBuffer pour les cérémonies WebAuthn ─────
  function b64urlToBuffer(base64url) {
    return urlBase64ToUint8Array(base64url).buffer;
  }
  function bufferToB64url(buffer) {
    const bytes = new Uint8Array(buffer);
    let str = '';
    for (const b of bytes) str += String.fromCharCode(b);
    return window.btoa(str).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  }

  let deferredPrompt = null;
  let webauthnCeremonyPromise = null;

  // Capture l'invite d'installation native (Chrome/Edge sur Android & PC)
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    document.dispatchEvent(new CustomEvent('pwa:installable'));
  });

  window.addEventListener('appinstalled', () => {
    deferredPrompt = null;
    document.dispatchEvent(new CustomEvent('pwa:installed'));
  });

  // Enregistrement du Service Worker
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      const base = appBase();
      navigator.serviceWorker.register(base + 'sw.js', { scope: base }).catch(() => {});
    });
  }

  // API publique
  window.AfristayPWA = {
    isStandalone() {
      return window.matchMedia('(display-mode: standalone)').matches
        || window.matchMedia('(display-mode: fullscreen)').matches
        || window.navigator.standalone === true;
    },
    isIOS() {
      const ua = window.navigator.userAgent;
      return /iphone|ipad|ipod/i.test(ua)
        || (/Macintosh/.test(ua) && 'ontouchend' in document); // iPad iPadOS
    },
    canInstall() { return !!deferredPrompt; },
    async install() {
      if (!deferredPrompt) return false;
      deferredPrompt.prompt();
      const { outcome } = await deferredPrompt.userChoice;
      deferredPrompt = null;
      return outcome === 'accepted';
    },

    webauthnSupported() {
      return !!(window.PublicKeyCredential && navigator.credentials && navigator.credentials.create);
    },

    /**
     * Enregistre une passkey liée à cet appareil (preuve cryptographique
     * qu'une vraie cérémonie WebAuthn a eu lieu). Effectuée UNE SEULE FOIS par
     * appareil : le serveur mint alors un device_token opaque, stocké en
     * localStorage et renvoyé tel quel à chaque connexion ensuite (voir
     * getDeviceToken ci-dessous) — pas de nouvelle invite biométrique à
     * chaque tentative. Échoue silencieusement si l'authenticateur exige un
     * geste utilisateur non disponible ici — retentée depuis submit() dans ce cas.
     */
    async ensureWebauthnCredential(baseUrl) {
      if (localStorage.getItem('device_token')) return true;
      if (!this.isStandalone() || !this.webauthnSupported()) return false;

      // Verrou anti-concurrence : init() (au chargement) et submit() (au clic)
      // peuvent toutes deux appeler cette méthode avant que le token existe.
      // navigator.credentials.create() rejette avec "A request is already
      // pending." si une deuxième cérémonie démarre pendant que la première
      // attend encore le geste biométrique — on fait donc attendre le second
      // appelant sur la MÊME promesse plutôt que de relancer une cérémonie.
      if (webauthnCeremonyPromise) return webauthnCeremonyPromise;

      webauthnCeremonyPromise = (async () => {
        try {
          const optRes = await fetch(baseUrl + '/api/auth/webauthn/register-options', { method: 'POST' });
          const optData = await optRes.json();
          const { state, publicKey } = optData?.data ?? {};
          if (!state || !publicKey) return false;

          const publicKeyOptions = {
            ...publicKey,
            challenge: b64urlToBuffer(publicKey.challenge),
            user: { ...publicKey.user, id: b64urlToBuffer(publicKey.user.id) },
          };

          const credential = await navigator.credentials.create({ publicKey: publicKeyOptions });
          if (!credential) return false;

          const credentialJson = {
            id: credential.id,
            rawId: bufferToB64url(credential.rawId),
            type: credential.type,
            response: {
              clientDataJSON: bufferToB64url(credential.response.clientDataJSON),
              attestationObject: bufferToB64url(credential.response.attestationObject),
              transports: credential.response.getTransports ? credential.response.getTransports() : [],
            },
          };

          const verifyRes = await fetch(baseUrl + '/api/auth/webauthn/register-verify', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ state, credential: credentialJson }),
          });
          const verifyData = await verifyRes.json();
          const deviceToken = verifyData?.data?.device_token;
          if (verifyData.success && deviceToken) {
            localStorage.setItem('device_token', deviceToken);
            return true;
          }
          // Visible dans la console : sans ça, un échec de vérification serveur
          // était totalement silencieux et la cérémonie (avec sa boîte de dialogue
          // native) se representait à chaque visite sans qu'on sache pourquoi.
          console.warn('[Afristay] Échec enregistrement WebAuthn :', verifyData.message || verifyData);
        } catch (e) {
          // Hors-ligne, geste utilisateur manquant, ou annulé — retenté plus tard.
          console.warn('[Afristay] Cérémonie WebAuthn interrompue :', e?.message || e);
        }
        return false;
      })();

      try {
        return await webauthnCeremonyPromise;
      } finally {
        webauthnCeremonyPromise = null;
      }
    },

    /** Jeton d'appareil miné à l'enregistrement, à envoyer tel quel à /api/auth/login. */
    getDeviceToken() {
      return localStorage.getItem('device_token');
    },

    // ── Notifications push (hors application) ─────────────────────────────
    pushSupported() {
      return 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
    },
    async pushSubscription() {
      if (!this.pushSupported()) return null;
      const reg = await navigator.serviceWorker.ready;
      return reg.pushManager.getSubscription();
    },
    /**
     * Vérité serveur, pas seulement navigateur : un abonnement PushManager peut
     * exister côté navigateur alors que l'appel POST /api/push/subscribe d'origine
     * a échoué (réseau, token expiré à ce moment-là…) — dans ce cas le bouton ne
     * doit PAS s'afficher comme activé. On renvoie l'abonnement uniquement si le
     * serveur vient de le confirmer (upsert idempotent, sans effet de bord si déjà connu).
     */
    async syncPushSubscription(baseUrl, token) {
      if (!this.pushSupported()) return null;
      const reg = await navigator.serviceWorker.ready;
      const sub = await reg.pushManager.getSubscription();
      if (!sub) return null;

      try {
        const res = await fetch(baseUrl + '/api/push/subscribe', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', Authorization: 'Bearer ' + token },
          body: JSON.stringify(sub.toJSON()),
        });
        return res.ok ? sub : null;
      } catch (e) {
        return null; // hors-ligne — on ne peut pas confirmer, on ne prétend pas être activé
      }
    },
    /** Demande la permission navigateur, s'abonne au PushManager, envoie l'abonnement au serveur. */
    async enablePush(baseUrl, token) {
      if (!this.pushSupported()) return false;
      if (Notification.permission === 'denied') return false;

      const permission = await Notification.requestPermission();
      if (permission !== 'granted') return false;

      const reg = await navigator.serviceWorker.ready;
      let sub = await reg.pushManager.getSubscription();
      if (!sub) {
        const keyRes = await fetch(baseUrl + '/api/push/public-key', {
          headers: { Authorization: 'Bearer ' + token },
        }).then((r) => r.json());
        const publicKey = keyRes.data?.public_key;
        if (!publicKey) return false;

        sub = await reg.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(publicKey),
        });
      }

      const res = await fetch(baseUrl + '/api/push/subscribe', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Authorization: 'Bearer ' + token },
        body: JSON.stringify(sub.toJSON()),
      });
      return res.ok;
    },
    /** Résilie l'abonnement PushManager côté navigateur et prévient le serveur. */
    async disablePush(baseUrl, token) {
      if (!this.pushSupported()) return false;
      const reg = await navigator.serviceWorker.ready;
      const sub = await reg.pushManager.getSubscription();
      if (!sub) return true;

      const endpoint = sub.endpoint;
      await sub.unsubscribe();
      await fetch(baseUrl + '/api/push/unsubscribe', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Authorization: 'Bearer ' + token },
        body: JSON.stringify({ endpoint }),
      });
      return true;
    },
  };
})();

/**
 * Composant Alpine pour gater l'accès selon l'installation.
 * Utilisé via x-data="pwaGate()" : expose standalone / installable / isIOS / install().
 */
function pwaGate() {
  return {
    standalone: window.AfristayPWA.isStandalone(),
    installable: window.AfristayPWA.canInstall(),
    isIOS: window.AfristayPWA.isIOS(),
    justInstalled: false,

    init() {
      document.addEventListener('pwa:installable', () => { this.installable = true; });
      document.addEventListener('pwa:installed', () => { this.justInstalled = true; });
      // Si l'utilisateur passe en mode app (lancement standalone), recharger l'état
      window.matchMedia('(display-mode: standalone)').addEventListener?.('change', (e) => {
        if (e.matches) this.standalone = true;
      });
    },

    async install() {
      const ok = await window.AfristayPWA.install();
      if (!ok) this.installable = window.AfristayPWA.canInstall();
    },
  };
}
