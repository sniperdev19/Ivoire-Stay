/* ============================================================
   Ivoire Stay — Page inscription (saas/register.php)
   Étapes: 1 Compte → 2 Établissement → 3 Paiement (plans payants)
   ============================================================ */

function registerPage(baseUrl) {
  const base = (baseUrl || '').replace(/\/$/, '');
  return {
    base,
    form: {
      name: '', email: '', phone: '',
      password: '', password_confirm: '',
      establishment_name: '', establishment_type: 'hotel',
    },
    step: 1,
    loading: false,
    error: null,
    showPass: false,

    plan: 'starter',
    billing: 'monthly',
    payMethod: 'orange',
    token: null,
    payLoading: false,
    payError: null,

    planData: {
      starter:  { name: 'Starter',  price: { monthly: 0,     yearly: 0      }, monthlyEq: { monthly: 0,     yearly: 0     }, free: true },
      pro:      { name: 'Pro',      price: { monthly: 9000,  yearly: 86400  }, monthlyEq: { monthly: 9000,  yearly: 7200  } },
      business: { name: 'Business', price: { monthly: 20000, yearly: 192000 }, monthlyEq: { monthly: 20000, yearly: 16000 } },
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

    validateStep1() {
      this.error = null;
      if (!this.form.name.trim())                                   return void (this.error = 'Nom complet requis.');
      if (!this.form.email.match(/^[^@]+@[^@]+\.[^@]+$/))          return void (this.error = 'Email invalide.');
      if (!this.form.phone.trim())                                   return void (this.error = 'Téléphone requis.');
      if (this.form.password.length < 8)                            return void (this.error = 'Mot de passe : 8 caractères minimum.');
      if (this.form.password !== this.form.password_confirm)        return void (this.error = 'Les mots de passe ne correspondent pas.');
      this.step = 2;
    },

    async submit() {
      this.error = null;
      if (!this.form.establishment_name.trim()) { this.error = "Nom de l'établissement requis."; return; }
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
            window.location.href = base + '/login?registered=1';
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
