/* ============================================================
   Afristay — Profil agent commercial (src/templates/agent/profile.php)
   ============================================================ */

/* Même flag/pattern que saas-settings.js/admin-settings.js — voir
   AgentWebauthnLoginService (table agent_webauthn_login_credentials,
   séparée de webauthn_login_credentials car un agent n'est pas un `users`). */
const BIOMETRIC_LOGIN_ENABLED = true;

function agentProfilePage(baseUrl) {
  return {
    agent: {},
    form: { nom: '', numero: '', operateur_money: '' },
    saving:    false,
    saveError: null,
    saveOk:    false,

    pwForm: { current_password: '', new_password: '', new_password_confirm: '' },
    pwSaving: false,
    pwError:  null,
    pwOk:     false,

    init() {
      const token = localStorage.getItem('agent_token');
      if (!token) {
        window.location.href = baseUrl + '/agent/login';
        return;
      }
      const cached = localStorage.getItem('agent');
      if (cached) { try { this.agent = JSON.parse(cached); this.fillForm(); } catch (_) {} }
      this.load();
      this.loadBiometricCredentials();
    },

    get initials() {
      const words = (this.agent.nom || '').trim().split(/\s+/).filter(Boolean);
      if (!words.length) return 'AS';
      return words.slice(0, 2).map(w => w[0].toUpperCase()).join('');
    },

    fillForm() {
      this.form.nom             = this.agent.nom             || '';
      this.form.numero          = this.agent.numero          || '';
      this.form.operateur_money = this.agent.operateur_money || '';
    },

    authHeaders() {
      return {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + localStorage.getItem('agent_token'),
      };
    },

    async load() {
      try {
        const res  = await fetch(baseUrl + '/api/agent/me', { headers: this.authHeaders() });
        const data = await res.json();
        if (res.status === 401) { this.logout(); return; }
        if (data.success) {
          this.agent = data.data.agent;
          localStorage.setItem('agent', JSON.stringify(this.agent));
          this.fillForm();
        }
      } catch (_) { /* réseau indisponible — on garde le cache déjà affiché */ }
    },

    async saveProfile() {
      this.saving = true;
      this.saveError = null;
      this.saveOk = false;
      try {
        const res  = await fetch(baseUrl + '/api/agent/profile', {
          method:  'PUT',
          headers: this.authHeaders(),
          body:    JSON.stringify(this.form),
        });
        const data = await res.json();
        if (data.success) {
          this.agent = data.data;
          localStorage.setItem('agent', JSON.stringify(this.agent));
          this.fillForm();
          this.saveOk = true;
          setTimeout(() => { this.saveOk = false; }, 2500);
        } else {
          this.saveError = data.message || 'Erreur lors de la mise à jour.';
        }
      } catch (_) {
        this.saveError = 'Erreur réseau.';
      } finally {
        this.saving = false;
      }
    },

    async changePassword() {
      this.pwError = null;
      this.pwOk    = false;

      if (!this.pwForm.current_password || !this.pwForm.new_password) {
        this.pwError = 'Tous les champs sont requis.';
        return;
      }
      if (this.pwForm.new_password !== this.pwForm.new_password_confirm) {
        this.pwError = 'Les mots de passe ne correspondent pas.';
        return;
      }

      this.pwSaving = true;
      try {
        const res  = await fetch(baseUrl + '/api/agent/change-password', {
          method:  'POST',
          headers: this.authHeaders(),
          body:    JSON.stringify({
            current_password: this.pwForm.current_password,
            new_password:     this.pwForm.new_password,
          }),
        });
        const data = await res.json();
        if (data.success) {
          this.pwOk = true;
          this.pwForm = { current_password: '', new_password: '', new_password_confirm: '' };
        } else {
          this.pwError = data.message || 'Erreur lors du changement de mot de passe.';
        }
      } catch (_) {
        this.pwError = 'Erreur réseau.';
      } finally {
        this.pwSaving = false;
      }
    },

    // ── Connexion par empreinte digitale (WebAuthn/passkey) — facultative ──
    biometricSupported: BIOMETRIC_LOGIN_ENABLED && !!(window.AfristayPWA && window.AfristayPWA.webauthnSupported()),
    biometricCredentials: [],
    biometricLoading: false,
    biometricEnrolling: false,
    biometricActionLoading: false,
    biometricError: null,
    biometricOk: false,

    async loadBiometricCredentials() {
      if (!this.biometricSupported) return;
      this.biometricLoading = true;
      try {
        const res  = await fetch(baseUrl + '/api/agent/webauthn/login-credential', { headers: this.authHeaders() });
        const data = await res.json();
        if (data.success) this.biometricCredentials = data.data ?? [];
      } catch (e) { /* liste vide si hors-ligne */ }
      finally { this.biometricLoading = false; }
    },

    async enrollBiometric() {
      if (this.biometricEnrolling) return;
      this.biometricError = null;
      this.biometricEnrolling = true;
      try {
        const optRes  = await fetch(baseUrl + '/api/agent/webauthn/login-credential/register-options', {
          method: 'POST', headers: this.authHeaders(),
        });
        const optData = await optRes.json();
        const { state, publicKey } = optData?.data ?? {};
        if (!optData.success || !state || !publicKey) {
          this.biometricError = optData.message || "Impossible de démarrer l'activation.";
          return;
        }

        const publicKeyOptions = {
          ...publicKey,
          challenge: window.AfristayPWA.b64urlToBuffer(publicKey.challenge),
          user: { ...publicKey.user, id: window.AfristayPWA.b64urlToBuffer(publicKey.user.id) },
          excludeCredentials: (publicKey.excludeCredentials || []).map(c => ({ ...c, id: window.AfristayPWA.b64urlToBuffer(c.id) })),
        };

        const credential = await navigator.credentials.create({ publicKey: publicKeyOptions });
        if (!credential) { this.biometricError = 'Cérémonie annulée.'; return; }

        const credentialJson = {
          id: credential.id,
          rawId: window.AfristayPWA.bufferToB64url(credential.rawId),
          type: credential.type,
          response: {
            clientDataJSON: window.AfristayPWA.bufferToB64url(credential.response.clientDataJSON),
            attestationObject: window.AfristayPWA.bufferToB64url(credential.response.attestationObject),
            transports: credential.response.getTransports ? credential.response.getTransports() : [],
          },
        };

        const verifyRes  = await fetch(baseUrl + '/api/agent/webauthn/login-credential/register-verify', {
          method: 'POST', headers: this.authHeaders(),
          body: JSON.stringify({ state, credential: credentialJson }),
        });
        const verifyData = await verifyRes.json();
        if (verifyData.success) {
          this.biometricOk = true;
          setTimeout(() => { this.biometricOk = false; }, 2500);
          await this.loadBiometricCredentials();
        } else {
          this.biometricError = verifyData.message || "Échec de l'activation.";
        }
      } catch (e) {
        this.biometricError = e?.name === 'NotAllowedError' ? null : "Impossible d'activer l'empreinte sur cet appareil.";
      } finally {
        this.biometricEnrolling = false;
      }
    },

    async revokeBiometric(id) {
      this.biometricActionLoading = true;
      try {
        const res  = await fetch(baseUrl + '/api/agent/webauthn/login-credential/' + id, {
          method: 'DELETE', headers: this.authHeaders(),
        });
        const data = await res.json();
        if (data.success) await this.loadBiometricCredentials();
      } catch (e) { /* réessayer plus tard */ }
      finally { this.biometricActionLoading = false; }
    },

    logout() {
      fetch(baseUrl + '/api/agent/logout', { method: 'POST', headers: this.authHeaders() }).catch(() => {});
      localStorage.removeItem('agent_token');
      localStorage.removeItem('agent');
      window.location.href = baseUrl + '/agent/login';
    },
  };
}
