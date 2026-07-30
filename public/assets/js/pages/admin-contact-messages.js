/* ============================================================
   Afristay — Admin plateforme : Messages de contact
   (src/templates/admin/contact-messages.php)
   ============================================================ */

function adminContactMessagesPage(baseUrl) {
  return {
    loading:  true,
    messages: [],
    selected: null,

    async init() {
      await this.loadMessages();
    },

    authHeaders() {
      return { 'Authorization': 'Bearer ' + localStorage.getItem('token') };
    },

    async loadMessages() {
      this.loading = true;
      try {
        const res  = await fetch(baseUrl + '/api/admin/contact-messages', { headers: this.authHeaders() });
        const data = await res.json();
        this.messages = data.success ? (data.data ?? []) : [];
      } catch (e) {
        this.messages = [];
      } finally {
        this.loading = false;
      }
    },

    async openMessage(m) {
      this.selected = m;
      if (!m.read_at) {
        try {
          await fetch(baseUrl + '/api/admin/contact-messages/' + m.id + '/read', {
            method: 'POST', headers: this.authHeaders(),
          });
          m.read_at = new Date().toISOString();
        } catch (e) { /* pas bloquant — le statut se remettra à jour au prochain chargement */ }
      }
    },

    async deleteMessage(m) {
      if (!m || !confirm('Supprimer ce message ?')) return;
      try {
        const res  = await fetch(baseUrl + '/api/admin/contact-messages/' + m.id, {
          method: 'DELETE', headers: this.authHeaders(),
        });
        const data = await res.json();
        if (data.success) {
          this.messages = this.messages.filter(x => x.id !== m.id);
          this.selected = null;
        }
      } catch (e) { /* silencieux */ }
    },

    formatDateTime(d) {
      if (!d) return '-';
      return new Date(d).toLocaleString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    },
  };
}
