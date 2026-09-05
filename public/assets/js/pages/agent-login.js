/* ============================================================
   Afristay — Connexion agent commercial
   Dépend de pwa.js (chargé avant) qui expose window.AfristayPWA.
   Même gate PWA que le reste (login.js) : accès app installée uniquement.
   ============================================================ */

/* Même flag/pattern que login.js — voir AgentWebauthnLoginService (table
   agent_webauthn_login_credentials, séparée car un agent n'est pas un `users`). */
const BIOMETRIC_LOGIN_ENABLED = true;

function agentLoginPage(baseUrl) {
  return {
    isApp:          false,
    installable:    false,
    installFeedback: null,

    form:     { numero: '', password: '' },
    loading:  false,
    error:    null,
    showPass: false,

    /* ── Connexion par empreinte (facultative, en plus du mot de passe) ── */
    biometricAvailable: false,
    biometricLoading:   false,

    init() {
      const pwa = window.AfristayPWA;

      this.isApp      = pwa.isStandalone();
      this.installable = pwa.canInstall();

      document.addEventListener('pwa:installable', () => {
        this.installable = true;
      });

      document.addEventListener('pwa:installed', () => {
        this.isApp      = true;
        this.installable = false;
      });

      window.matchMedia('(display-mode: standalone)').addEventListener?.('change', e => {
        if (e.matches) this.isApp = true;
      });

      if (BIOMETRIC_LOGIN_ENABLED && pwa.webauthnSupported() && window.PublicKeyCredential?.isUserVerifyingPlatformAuthenticatorAvailable) {
        window.PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable()
          .then(available => { this.biometricAvailable = available; })
          .catch(() => {});
      }
    },

    async loginWithBiometric() {
      if (this.biometricLoading || this.loading) return;
      this.error = null;
      this.biometricLoading = true;
      try {
        const pwa     = window.AfristayPWA;
        const optRes  = await fetch(baseUrl + '/api/agent/webauthn/login-options', { method: 'POST' });
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

        const res  = await fetch(baseUrl + '/api/agent/webauthn/login-verify', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ state, credential: credentialJson }),
        });
        const data = await res.json();
        if (data.success) {
          localStorage.setItem('agent_token', data.data.token);
          localStorage.setItem('agent', JSON.stringify(data.data.agent));
          window.location.href = baseUrl + '/agent/dashboard';
        } else {
          this.error = data.message ?? 'Échec de la connexion par empreinte. Utilisez votre mot de passe.';
        }
      } catch (e) {
        if (e?.name !== 'NotAllowedError') {
          this.error = 'Erreur réseau. Utilisez votre mot de passe.';
        }
      } finally {
        this.biometricLoading = false;
      }
    },

    async install() {
      const pwa = window.AfristayPWA;

      if (this.installable) {
        const accepted = await pwa.install();
        this.installable = pwa.canInstall();
        if (accepted) {
          setTimeout(() => window.location.reload(), 700);
        }
      } else if (pwa.isIOS()) {
        this.installFeedback = 'ios';
      } else {
        this.installFeedback = 'unavailable';
        setTimeout(() => { this.installFeedback = null; }, 4000);
      }
    },

    async submit() {
      this.error = null;
      if (!this.form.numero.trim() || !this.form.password) {
        this.error = 'Veuillez remplir tous les champs.';
        return;
      }
      this.loading = true;
      try {
        const res = await fetch(baseUrl + '/api/agent/login', {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify(this.form),
        });
        const data = await res.json();
        if (data.success) {
          localStorage.setItem('agent_token', data.data.token);
          localStorage.setItem('agent', JSON.stringify(data.data.agent));
          window.location.href = baseUrl + '/agent/dashboard';
        } else {
          this.error = data.message ?? 'Numéro ou mot de passe incorrect.';
        }
      } catch (_) {
        this.error = 'Erreur réseau. Veuillez réessayer.';
      } finally {
        this.loading = false;
      }
    },
  };
}
