/* ============================================================
   Afristay — Admin plateforme : Newsletter (src/templates/admin/newsletter.php)
   ============================================================ */

function adminNewsletterPage(baseUrl) {
  return {
    loading:         true,
    subscriberCount: 0,
    campaigns:       [],
    form:            { subject: '', body: '' },
    sending:         false,
    error:           null,
    success:         null,

    async init() {
      await Promise.all([this.loadSubscribers(), this.loadCampaigns()]);
    },

    authHeaders() {
      return { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + localStorage.getItem('token') };
    },

    async loadSubscribers() {
      try {
        const res  = await fetch(baseUrl + '/api/admin/newsletter/subscribers', { headers: this.authHeaders() });
        const data = await res.json();
        this.subscriberCount = data.success ? (data.data?.count ?? 0) : 0;
      } catch (e) {
        this.subscriberCount = 0;
      }
    },

    async loadCampaigns() {
      this.loading = true;
      try {
        const res  = await fetch(baseUrl + '/api/admin/newsletter/campaigns', { headers: this.authHeaders() });
        const data = await res.json();
        this.campaigns = data.success ? (data.data ?? []) : [];
      } catch (e) {
        this.campaigns = [];
      } finally {
        this.loading = false;
      }
    },

    async send() {
      if (!confirm(`Envoyer cette campagne à ${this.subscriberCount} abonné(s) ? Cette action est irréversible.`)) return;

      this.sending = true;
      this.error   = null;
      this.success = null;
      try {
        const res  = await fetch(baseUrl + '/api/admin/newsletter/campaigns', {
          method:  'POST',
          headers: this.authHeaders(),
          body:    JSON.stringify(this.form),
        });
        const data = await res.json();
        if (data.success) {
          this.success = data.message;
          this.form = { subject: '', body: '' };
          await this.loadCampaigns();
        } else {
          this.error = data.message || 'Erreur lors de l\'envoi.';
        }
      } catch (e) {
        this.error = 'Erreur réseau.';
      } finally {
        this.sending = false;
      }
    },

    formatDateTime(d) {
      if (!d) return '-';
      return new Date(d).toLocaleString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    },
  };
}
