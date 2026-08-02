/* ============================================================
   Afristay — Page inscription (saas/register.php)
   Étapes: 1 Compte → 2 Établissement → 3 Paiement (plans payants)
   ============================================================ */

function registerPage(baseUrl) {
  const base = (baseUrl || '').replace(/\/$/, '');
  return {
    base,
    form: {
      name: '', email: '', phone: '',
      password: '', password_confirm: '',
      establishment_name: '', establishment_type: 'hotel', establishment_city: '',
    },
    step: 1,
    loading: false,
    error: null,
    showPass: false,

    plan: 'starter',
    billing: 'monthly',
    payMethod: 'wave',
    token: null,
    payLoading: false,
    payError: null,

    planData: {
      starter:  { name: 'Gratuit',  price: { monthly: 0,     yearly: 0      }, monthlyEq: { monthly: 0,     yearly: 0     }, free: true },
      pro:      { name: 'Premium',  price: { monthly: 9000,  yearly: 86400  }, monthlyEq: { monthly: 9000,  yearly: 7200  } },
      business: { name: 'Premium+', price: { monthly: 20000, yearly: 192000 }, monthlyEq: { monthly: 20000, yearly: 16000 } },
    },

    get isPaid()  { return this.plan !== 'starter'; },
    get current() { return this.planData[this.plan] || this.planData.starter; },
    get total()   { return (this.current.price || {})[this.billing] || 0; },
    get savings() {
      if (this.billing !== 'yearly' || !this.isPaid) return 0;
      return (this.current.price.monthly * 12) - this.current.price.yearly;
    },

    init() {
      const p  = new URLSearchParams(window.location.search);
      const pl = p.get('plan');
      const bl = p.get('billing');
      if (this.planData[pl]) this.plan = pl;
      if (bl === 'yearly' || bl === 'monthly') this.billing = bl;
    },

    /* Retire les chiffres au fil de la saisie (nom complet). */
    sanitizeName() { this.form.name = this.form.name.replace(/[0-9]/g, ''); },

    /* Ne garde que chiffres / espace / + au fil de la saisie (téléphone). */
    sanitizePhone() { this.form.phone = this.form.phone.replace(/[^0-9+\s]/g, ''); },

    /* Numéros ivoiriens (plan de numérotation 2021) : 10 chiffres locaux
       commençant par 01, 05 ou 07, avec ou sans indicatif +225. */
    isValidCiPhone(v) {
      let digits = (v || '').replace(/\D/g, '');
      if (digits.startsWith('225')) digits = digits.slice(3);
      return digits.length === 10 && /^(01|05|07)/.test(digits);
    },

    get passwordScore() {
      const p = this.form.password || '';
      if (!p) return 0;
      let score = 0;
      if (p.length >= 8) score++;
      if (p.length >= 12) score++;
      if (/[a-z]/.test(p) && /[A-Z]/.test(p)) score++;
      if (/\d/.test(p)) score++;
      if (/[^a-zA-Z0-9]/.test(p)) score++;
      return Math.min(score, 4);
    },
    get passwordScoreLabel() { return ['Très faible', 'Faible', 'Moyen', 'Fort', 'Très fort'][this.passwordScore]; },
    get passwordScoreColor() { return ['#DC2626', '#EA580C', '#D97706', '#65A30D', '#16A34A'][this.passwordScore]; },
    get passwordsMatch()    { return this.form.password_confirm.length > 0 && this.form.password === this.form.password_confirm; },
    get passwordsMismatch() { return this.form.password_confirm.length > 0 && this.form.password !== this.form.password_confirm; },

    validateStep1() {
      this.error = null;
      if (!this.form.name.trim())                                   return void (this.error = 'Nom complet requis.');
      if (!this.form.email.match(/^[^@]+@[^@]+\.[^@]+$/))          return void (this.error = 'Email invalide.');
      if (!this.form.phone.trim())                                   return void (this.error = 'Téléphone requis.');
      if (!this.isValidCiPhone(this.form.phone))                    return void (this.error = 'Téléphone invalide : 10 chiffres commençant par 01, 05 ou 07.');
      if (this.form.password.length < 8)                            return void (this.error = 'Mot de passe : 8 caractères minimum.');
      if (!/[a-zA-Z]/.test(this.form.password) || !/\d/.test(this.form.password))
                                                                       return void (this.error = 'Mot de passe : au moins une lettre et un chiffre.');
      if (this.form.password !== this.form.password_confirm)        return void (this.error = 'Les mots de passe ne correspondent pas.');
      this.step = 2;
    },

    async submit() {
      this.error = null;
      if (!this.form.establishment_name.trim()) { this.error = "Nom de l'établissement requis."; return; }
      if (!this.form.establishment_city.trim()) { this.error = "Ville requise, sans elle votre établissement n'apparaîtra pas dans les recherches."; return; }
      this.loading = true;
      try {
        const res  = await fetch(base + '/api/auth/register', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(this.form),
        });
        const data = await res.json();
        if (data.success) {
          localStorage.setItem('token', data.data.token);
          localStorage.setItem('user', JSON.stringify(data.data.user));
          const estabs = data.data.establishments ?? [];
          localStorage.setItem('establishments', JSON.stringify(estabs));
          if (estabs.length) localStorage.setItem('establishment_id', estabs[0].id);
          if (this.isPaid) {
            this.token = data.data.token;
            this.step  = 3;
          } else {
            window.location.href = base + '/install';
          }
        } else {
          this.error = data.message ?? "Erreur lors de l'inscription.";
        }
      } catch (_) {
        this.error = 'Erreur réseau. Veuillez réessayer.';
      } finally {
        this.loading = false;
      }
    },

    async pay() {
      this.payLoading = true;
      this.payError   = null;
      try {
        const res  = await fetch(base + '/api/subscriptions/initiate', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + this.token },
          body: JSON.stringify({ plan: this.plan, billing: this.billing, pay_method: this.payMethod }),
        });
        const data = await res.json();
        if (!data.success) { this.payError = data.message || 'Une erreur est survenue.'; return; }
        if (data.data?.payment_url) {
          window.location.href = data.data.payment_url;
        } else {
          this.payError = 'URL de paiement introuvable.';
        }
      } catch (_) {
        this.payError = 'Erreur réseau. Veuillez réessayer.';
      } finally {
        this.payLoading = false;
      }
    },

    skipPayment() { window.location.href = base + '/login?registered=1'; },

    fmt(n) { return new Intl.NumberFormat('fr-CI').format(n) + ' FCFA'; },
  };
}
