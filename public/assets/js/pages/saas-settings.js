/* ============================================================
   Ivoire Stay — Page SaaS : Paramètres (src/templates/saas/settings.php)
   ============================================================ */

function settingsPage(baseUrl) {
  return {
  ...saasHelpers,

  establishment: null,
  loading: true,
  subscription: null,
  plans: {},
  featureLabels: {
    invoices:    'Facturation',
    payments:    'Gestion des paiements',
    expenses:    'Suivi des dépenses',
    reports:     'Rapports & Analyses',
    pdf:         'Export PDF',
    boost:       'Boost vitrine',
    multi_estab: 'Multi-établissements',
  },
  activeTab: 'general',
  saving: false,
  saveError: null,
  saveSuccess: false,
  isNewEstab: false,

  form: { name: '', type: 'hotel', address: '', city: '', phone: '', email: '', description: '', website: '' },

  paymentSuccess: false,
  paymentError: null,

  async verifyPayment(ref) {
    try {
      const res  = await fetch(baseUrl + '/api/subscriptions/verify/' + encodeURIComponent(ref), {
        headers: this.apiHeaders(),
      });
      const data = await res.json();
      if (data.success && data.data?.status === 'active') {
        // Rafraîchir l'établissement en DB → mettre à jour localStorage → recharger
        const estRes = await fetch(baseUrl + '/api/establishments/' + this.estId(), {
          headers: this.apiHeaders(),
        }).then(r => r.json());
        if (estRes.success) {
          const estab  = estRes.data?.establishment ?? estRes.data;
          const estabs = JSON.parse(localStorage.getItem('establishments') || '[]');
          const idx    = estabs.findIndex(e => String(e.id) === String(estab.id));
          if (idx !== -1) estabs[idx] = { ...estabs[idx], plan: estab.plan, plan_expires_at: estab.plan_expires_at };
          else estabs.push(estab);
          localStorage.setItem('establishments', JSON.stringify(estabs));
        }
        // Recharger pour que le sidebar prenne le nouveau plan
        window.location.href = window.location.pathname + '?activated=1';
      } else {
        this.paymentError = data.data?.gp_status === 'pending'
          ? 'Paiement en cours de traitement…'
          : 'Paiement non confirmé. Contactez le support si vous avez été débité.';
        this.activeTab = 'subscription';
      }
    } catch (e) {
      this.paymentError = 'Impossible de vérifier le paiement.';
    }
  },

  /* formatDate local : 'Aucune' pour null, mois en toutes lettres */
  formatDate(d) {
    if (!d) return 'Aucune';
    return new Date(d).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
  },

  async init() {
    this.loading = true;
    const id = this.estId();

    // Retour depuis GeniusPay après paiement
    const urlParams = new URLSearchParams(window.location.search);
    const payRef    = urlParams.get('ref');
    if (urlParams.get('sub') === 'ok' && payRef) {
      await this.verifyPayment(payRef);
      window.history.replaceState(null, '', window.location.pathname);
    }
    // Rechargement post-activation : afficher bannière succès
    if (urlParams.get('activated') === '1') {
      this.paymentSuccess = true;
      this.activeTab      = 'subscription';
      window.history.replaceState(null, '', window.location.pathname);
    }

    if (!id) {
      this.isNewEstab = true;
      try {
        const plansRes = await fetch(baseUrl + '/api/subscriptions/plans', { headers: this.apiHeaders() }).then(r => r.json());
        if (plansRes.success) this.plans = plansRes.data?.plans ?? plansRes.data ?? [];
      } catch(e) {}
      this.loading = false;
      return;
    }

    try {
      const [estRes, subRes, plansRes] = await Promise.all([
        fetch(baseUrl + '/api/establishments/' + id,                             { headers: this.apiHeaders() }).then(r => r.json()),
        fetch(baseUrl + '/api/subscriptions/status?establishment_id=' + id,      { headers: this.apiHeaders() }).then(r => r.json()),
        fetch(baseUrl + '/api/subscriptions/plans',                              { headers: this.apiHeaders() }).then(r => r.json()),
      ]);

      if (estRes.success) {
        this.establishment = estRes.data?.establishment ?? estRes.data;
        this.form = {
          name:        this.establishment?.name        ?? '',
          type:        this.establishment?.type        ?? 'hotel',
          address:     this.establishment?.address     ?? '',
          city:        this.establishment?.city        ?? '',
          phone:       this.establishment?.phone       ?? '',
          email:       this.establishment?.email       ?? '',
          description: this.establishment?.description ?? '',
          website:     this.establishment?.website     ?? '',
        };
      }
      if (subRes.success)   this.subscription = subRes.data?.subscription ?? subRes.data;
      if (plansRes.success) this.plans = plansRes.data?.plans ?? plansRes.data ?? [];
    } catch(e) {
      /* erreur réseau — formulaire reste vide */
    } finally {
      this.loading = false;
    }
  },

  async saveGeneral() {
    this.saving = true; this.saveError = null; this.saveSuccess = false;
    try {
      let url, method;
      if (this.isNewEstab) {
        if (!this.form.name?.trim()) { this.saveError = 'Le nom est requis.'; this.saving = false; return; }
        if (!this.form.city?.trim()) { this.saveError = 'La ville est requise.'; this.saving = false; return; }
        url    = baseUrl + '/api/establishments';
        method = 'POST';
      } else {
        url    = baseUrl + '/api/establishments/' + this.estId();
        method = 'PUT';
      }

      const res  = await fetch(url, { method, headers: this.apiHeaders(), body: JSON.stringify(this.form) });
      const data = await res.json();

      if (data.success) {
        this.saveSuccess = true;
        setTimeout(() => this.saveSuccess = false, 3000);

        if (this.isNewEstab) {
          const estab = data.data?.establishment ?? data.data;
          if (estab?.id) {
            localStorage.setItem('establishment_id', String(estab.id));
            localStorage.setItem('establishments', JSON.stringify([estab]));
          }
          this.isNewEstab    = false;
          this.establishment = estab;
          setTimeout(() => window.location.reload(), 1200);
        }
      } else {
        this.saveError = data.message ?? 'Erreur.';
      }
    } catch(e) {
      this.saveError = 'Erreur réseau.';
    } finally {
      this.saving = false;
    }
  },

  planLabel(p) { return { starter: 'Starter', pro: 'Pro', business: 'Business' }[p] ?? p; },
  planColor(p) { return { starter: 'badge-info', pro: 'badge-gold', business: 'badge-success' }[p] ?? 'badge'; },
  };
}
