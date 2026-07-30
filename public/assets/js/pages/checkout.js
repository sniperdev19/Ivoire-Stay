/* ============================================================
   Afristay — Page paiement abonnement (saas/checkout.php)
   ============================================================ */

function checkoutPage(baseUrl) {
  return {
    api: (baseUrl || '').replace(/\/$/, ''),
    token: null,

    plan: 'pro',
    billing: 'monthly',
    payMethod: 'wave',

    loading: false,
    error: null,
    prorationCredit: 0,

    plans: {
      pro: {
        name: 'Premium',
        nameEm: 'Premium.',
        tagline: 'Pour les établissements qui veulent accélérer.',
        prices:      { monthly: 9000,  yearly: 86400 },
        monthlyEq:   { monthly: 9000,  yearly: 7200  },
        features: [
          'Chambres illimitées',
          'Facturation & gestion des paiements',
          'Suivi des dépenses',
          'Rapports & Analyses',
          'Export PDF',
        ],
      },
      business: {
        name: 'Premium+',
        nameEm: 'Premium+.',
        tagline: 'Pour les groupes hôteliers et gestionnaires multi-sites.',
        prices:      { monthly: 20000, yearly: 192000 },
        monthlyEq:   { monthly: 20000, yearly: 16000  },
        features: [
          'Tout le plan Premium inclus',
          'Multi-établissements illimités',
          'Boost vitrine',
        ],
      },
    },

    get current()     { return this.plans[this.plan] || this.plans.pro; },
    get fullPrice()   { return this.current.prices[this.billing]; },
    get total()       { return Math.max(0, this.fullPrice - this.prorationCredit); },
    get monthlyEq()   { return this.current.monthlyEq[this.billing]; },
    get periodLabel() {
      if (this.billing !== 'yearly') return 'par mois';
      return 'par an (équivaut à ' + new Intl.NumberFormat('fr-CI').format(this.monthlyEq) + ' FCFA/mois)';
    },
    get savings() {
      if (this.billing !== 'yearly') return 0;
      return (this.current.prices.monthly * 12) - this.current.prices.yearly;
    },

    fmt(n) {
      return new Intl.NumberFormat('fr-CI').format(n) + ' FCFA';
    },

    async init() {
      this.token = localStorage.getItem('token');
      if (!this.token) {
        window.location.href = this.api + '/login?redirect=' + encodeURIComponent(window.location.href);
        return;
      }
      const p = new URLSearchParams(window.location.search);
      const plan    = p.get('plan');
      const billing = p.get('billing');
      if (plan && this.plans[plan])              this.plan    = plan;
      if (billing === 'monthly' || billing === 'yearly') this.billing = billing;

      try {
        const res  = await fetch(this.api + '/api/subscriptions/status', { headers: { Authorization: 'Bearer ' + this.token } });
        const data = await res.json();
        if (data.success) this.prorationCredit = Number(data.data?.proration_credit) || 0;
      } catch (e) {
        this.prorationCredit = 0;
      }

      // Retour depuis Genius Pay après un paiement refusé/annulé (error_url) : on
      // interroge /verify pour que le statut 'pending' initial soit bien remplacé
      // par 'failed' côté DB, plutôt que de rester "en attente" indéfiniment.
      const ref = p.get('ref');
      if (p.get('error') === '1' && ref) {
        try {
          await fetch(this.api + '/api/subscriptions/verify/' + encodeURIComponent(ref), {
            headers: { Authorization: 'Bearer ' + this.token },
          });
        } catch (e) { /* best-effort — le nettoyage opportuniste au prochain /status rattrapera sinon */ }
        this.error = 'Le paiement a échoué ou a été annulé. Vous pouvez réessayer.';
      }
    },

    setBilling(b) {
      this.billing = b;
      const url = new URL(window.location.href);
      url.searchParams.set('billing', b);
      window.history.replaceState(null, '', url.toString());
    },

    async submit() {
      if (this.loading) return;
      this.loading = true;
      this.error   = null;
      try {
        const res = await fetch(this.api + '/api/subscriptions/initiate', {
          method:  'POST',
          headers: {
            'Content-Type':  'application/json',
            'Authorization': 'Bearer ' + this.token,
          },
          body: JSON.stringify({
            plan:       this.plan,
            billing:    this.billing,
            pay_method: this.payMethod,
          }),
        });
        const data = await res.json();
        if (!data.success) {
          this.error = data.message || 'Une erreur est survenue.';
          return;
        }
        if (data.data?.payment_url) {
          window.location.href = data.data.payment_url;
        } else {
          this.error = 'URL de paiement introuvable.';
        }
      } catch (_) {
        this.error = 'Erreur réseau. Veuillez réessayer.';
      } finally {
        this.loading = false;
      }
    },
  };
}
