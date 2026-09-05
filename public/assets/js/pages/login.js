/* ============================================================
   Afristay — Page connexion SaaS
   Dépend de pwa.js (chargé avant) qui expose window.AfristayPWA.
   ============================================================ */

/* Désactivé le 2026-08-11 à la demande explicite de l'utilisateur — code et
   endpoints backend intacts (WebauthnLoginService, table
   webauthn_login_credentials), juste masqué côté UI, sur le même principe
   que ONLINE_PAYMENTS_ENABLED. Repasser à true pour réactiver le bouton
   secondaire et l'écran verrouillé prioritaire sur /login (voir aussi le
   même flag dans saas-settings.js pour la carte d'activation). */
const BIOMETRIC_LOGIN_ENABLED = true;

function loginPage(baseUrl) {
  return {
    /* ── PWA gate ── */
    isApp:          false,
    installable:    false,
    installFeedback: null,

    /* ── Formulaire ── */
    form:     { email: '', password: '' },
    loading:  false,
    error:    null,
    showPass: false,

    /* ── Connexion par empreinte (facultative, en plus du mot de passe ci-dessus) ── */
    biometricAvailable: false,
    biometricLoading:   false,
    /* Écran "verrouillé" (façon écran de verrouillage mobile), affiché EN PRIORITÉ sur
       le formulaire classique — uniquement si une empreinte a déjà été activée sur CET
       appareil (indice localStorage posé par saas-settings.js après une activation
       réussie, et retiré si la dernière empreinte du compte est révoquée). Toujours
       réversible via "Utiliser mes identifiants" (useCredentialsInstead ci-dessous) —
       ne bloque jamais l'accès au mot de passe. */
    biometricFirstMode: BIOMETRIC_LOGIN_ENABLED && localStorage.getItem('biometric_login_hint') === '1',
    /* Deuxième temps de l'écran verrouillé : passe à true au clic sur "Déverrouiller",
       révélant le bas-de-page avec le capteur — jamais déclenché automatiquement. */
    lockRevealed: false,

    init() {
      const pwa = window.AfristayPWA;

      /* Détection initiale (standalone = dans l'app installée) */
      this.isApp      = pwa.isStandalone();
      this.installable = pwa.canInstall();

      /* Mise à jour réactive quand le prompt devient disponible */
      document.addEventListener('pwa:installable', () => {
        this.installable = true;
      });

      /* Mise à jour quand l'app vient d'être installée */
      document.addEventListener('pwa:installed', () => {
        this.isApp      = true;
        this.installable = false;
      });

      /* Détecte le passage en mode standalone (ex : rechargement depuis l'app) */
      window.matchMedia('(display-mode: standalone)').addEventListener?.('change', e => {
        if (e.matches) this.isApp = true;
      });

      /* Le bouton n'apparaît que si l'appareil supporte réellement un
         authentificateur biométrique/PIN intégré (pas juste l'API WebAuthn en
         général — un ordinateur sans lecteur d'empreinte/webcam la supporte
         mais ne peut rien vérifier). On ne sait pas encore si CE compte a une
         empreinte enregistrée : ça se découvre au clic (flux "discoverable",
         pas besoin de connaître l'email au préalable). */
      if (BIOMETRIC_LOGIN_ENABLED && pwa.webauthnSupported() && window.PublicKeyCredential?.isUserVerifyingPlatformAuthenticatorAvailable) {
        window.PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable()
          .then(available => { this.biometricAvailable = available; })
          .catch(() => {});
      }
    },

    /** Stockage token/redirection — commun à la connexion mot de passe et empreinte. */
    handleLoginSuccess(data) {
      localStorage.setItem('token', data.data.token);
      localStorage.setItem('user', JSON.stringify(data.data.user));
      const isSuperadmin = data.data.user?.role === 'superadmin';

      /* Le superadmin (propriétaire de la plateforme) n'est rattaché à AUCUN
         établissement particulier — il pilote /admin, pas un hôtel donné.
         Establishment::forUser() lui renvoie TOUS les établissements de la
         plateforme (utile pour /admin/establishments), mais on ne doit surtout
         pas en sélectionner un au hasard comme « établissement actif ». */
      if (isSuperadmin) {
        localStorage.removeItem('establishment_id');
        localStorage.removeItem('establishments');
      } else {
        const estabs = data.data.establishments ?? [];
        localStorage.setItem('establishments', JSON.stringify(estabs));
        if (Array.isArray(estabs) && estabs.length > 0) {
          const estId = estabs[0].id ?? estabs[0].establishment_id;
          if (estId) localStorage.setItem('establishment_id', String(estId));
        } else {
          try {
            const payload = JSON.parse(atob(data.data.token.split('.')[1]));
            if (payload.establishment_id)
              localStorage.setItem('establishment_id', String(payload.establishment_id));
          } catch (_) {}
        }
      }

      /* Redirige vers l'URL d'origine si la page a été ouverte via ?redirect= ;
         sinon un superadmin (propriétaire de la plateforme) atterrit sur son
         propre espace, pas sur l'outil de gestion hôtelière. */
      const redirect = new URLSearchParams(window.location.search).get('redirect');
      window.location.href = redirect
        ? decodeURIComponent(redirect)
        : baseUrl + (isSuperadmin ? '/admin' : '/saas');
    },

    async loginWithBiometric() {
      if (this.biometricLoading || this.loading) return;
      this.error = null;
      this.biometricLoading = true;
      try {
        const pwa     = window.AfristayPWA;
        const optRes  = await fetch(baseUrl + '/api/auth/webauthn/login-options', { method: 'POST' });
        const optData = await optRes.json();
        const { state, publicKey } = optData?.data ?? {};
        if (!optData.success || !state || !publicKey) {
          this.error = "Connexion par empreinte indisponible pour l'instant.";
          return;
        }

        const publicKeyOptions = { ...publicKey, challenge: pwa.b64urlToBuffer(publicKey.challenge) };
        const assertion = await navigator.credentials.get({ publicKey: publicKeyOptions });
        if (!assertion) return; // annulé par l'utilisateur

        const credentialJson = {
          id: assertion.id,
          rawId: pwa.bufferToB64url(assertion.rawId),
          type: assertion.type,
          response: {
            clientDataJSON: pwa.bufferToB64url(assertion.response.clientDataJSON),
            authenticatorData: pwa.bufferToB64url(assertion.response.authenticatorData),
            signature: pwa.bufferToB64url(assertion.response.signature),
            userHandle: assertion.response.userHandle ? pwa.bufferToB64url(assertion.response.userHandle) : null,
          },
        };

        const res  = await fetch(baseUrl + '/api/auth/webauthn/login-verify', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ state, credential: credentialJson }),
        });
        const data = await res.json();
        if (data.success) {
          this.handleLoginSuccess(data);
        } else {
          this.error = data.message ?? 'Échec de la connexion par empreinte. Utilisez votre mot de passe.';
        }
      } catch (e) {
        // Annulation utilisateur (NotAllowedError) : pas une vraie erreur, on reste silencieux.
        if (e?.name !== 'NotAllowedError') {
          this.error = 'Erreur réseau. Utilisez votre mot de passe.';
        }
      } finally {
        this.biometricLoading = false;
      }
    },

    /** Repli explicite vers le formulaire classique depuis l'écran verrouillé. */
    useCredentialsInstead() {
      this.error = null;
      this.biometricFirstMode = false;
      this.lockRevealed = false;
    },

    async install() {
      const pwa = window.AfristayPWA;

      if (this.installable) {
        /* Prompt natif disponible (Chrome / Edge sur Android & PC) */
        const accepted = await pwa.install();
        this.installable = pwa.canInstall();
        if (accepted) {
          /* Petit délai puis on recharge pour détecter le mode standalone */
          setTimeout(() => window.location.reload(), 700);
        }
      } else if (pwa.isIOS()) {
        /* iOS Safari : pas de prompt natif, afficher les instructions */
        this.installFeedback = 'ios';
      } else {
        /* Navigateur non compatible ou déjà installé */
        this.installFeedback = 'unavailable';
        setTimeout(() => { this.installFeedback = null; }, 4000);
      }
    },

    async submit() {
      this.error = null;
      if (!this.form.email.trim() || !this.form.password) {
        this.error = 'Veuillez remplir tous les champs.';
        return;
      }
      this.loading = true;
      try {
        const res = await fetch(baseUrl + '/api/auth/login', {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify(this.form),
        });
        const data = await res.json();
        if (data.success) {
          this.handleLoginSuccess(data);
        } else {
          this.error = data.message ?? 'Email ou mot de passe incorrect.';
        }
      } catch (_) {
        this.error = 'Erreur réseau. Veuillez réessayer.';
      } finally {
        this.loading = false;
      }
    },
  };
}
