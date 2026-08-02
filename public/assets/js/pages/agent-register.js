/* ============================================================
   Afristay — Inscription agent commercial
   Dépend de pwa.js (chargé avant) qui expose window.AfristayPWA.
   ============================================================ */

function agentRegisterPage(baseUrl) {
  return {
    isApp:          false,
    installable:    false,
    installFeedback: null,

    form: { nom: '', numero: '', operateur_money: '', password: '', password_confirm: '' },
    loading:  false,
    error:    null,
    showPass: false,

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

    isValidCiPhone(v) {
      let digits = (v || '').replace(/\D/g, '');
      if (digits.startsWith('225')) digits = digits.slice(3);
      return digits.length === 10 && /^(01|05|07)/.test(digits);
    },

    async submit() {
      this.error = null;
      if (!this.form.nom.trim())                                      { this.error = 'Le nom est requis.'; return; }
      if (!this.isValidCiPhone(this.form.numero))                     { this.error = 'Téléphone invalide : 10 chiffres commençant par 01, 05 ou 07.'; return; }
      if (!this.form.operateur_money)                                 { this.error = "Choisissez un opérateur Mobile Money."; return; }
      if (this.form.password.length < 8)                              { this.error = 'Mot de passe : 8 caractères minimum.'; return; }
      if (!/[a-zA-Z]/.test(this.form.password) || !/\d/.test(this.form.password)) {
        this.error = 'Mot de passe : au moins une lettre et un chiffre.'; return;
      }
      if (this.form.password !== this.form.password_confirm)         { this.error = 'Les mots de passe ne correspondent pas.'; return; }

      this.loading = true;
      try {
        const res = await fetch(baseUrl + '/api/agent/register', {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify({
            nom: this.form.nom,
            numero: this.form.numero,
            operateur_money: this.form.operateur_money,
            password: this.form.password,
          }),
        });
        const data = await res.json();
        if (data.success) {
          localStorage.setItem('agent_token', data.data.token);
          localStorage.setItem('agent', JSON.stringify(data.data.agent));
          window.location.href = baseUrl + '/agent/dashboard';
        } else {
          this.error = data.message ?? 'Impossible de créer le compte.';
        }
      } catch (_) {
        this.error = 'Erreur réseau. Veuillez réessayer.';
      } finally {
        this.loading = false;
      }
    },
  };
}
