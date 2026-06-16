/* ============================================================
   Ivoire Stay — PWA : enregistrement du Service Worker,
   détection du mode d'affichage (navigateur vs app installée),
   et gestion de l'invite d'installation (Android / iOS / PC).
   Chargé avant Alpine sur toutes les pages.
   ============================================================ */
(function () {
  // Déduit la base de l'app depuis le <link rel="manifest"> (gère le sous-dossier)
  function appBase() {
    const m = document.querySelector('link[rel="manifest"]');
    if (m) return m.href.replace(/manifest\.webmanifest.*$/, '');
    return location.href.replace(/\/[^\/]*$/, '/');
  }

  let deferredPrompt = null;

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
  window.IvoireStayPWA = {
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
  };
})();

/**
 * Composant Alpine pour gater l'accès selon l'installation.
 * Utilisé via x-data="pwaGate()" : expose standalone / installable / isIOS / install().
 */
function pwaGate() {
  return {
    standalone: window.IvoireStayPWA.isStandalone(),
    installable: window.IvoireStayPWA.canInstall(),
    isIOS: window.IvoireStayPWA.isIOS(),
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
      const ok = await window.IvoireStayPWA.install();
      if (!ok) this.installable = window.IvoireStayPWA.canInstall();
    },
  };
}
