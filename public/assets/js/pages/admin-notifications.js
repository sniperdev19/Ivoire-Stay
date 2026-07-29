/* ============================================================
   Afristay — Admin plateforme : Notifications (src/templates/admin/notifications.php)
   ============================================================ */

function adminNotificationsPage(baseUrl) {
  return {
    form: { title: '', message: '' },
    sending: false,
    error:   null,
    success: null,

    init() {},

    authHeaders() {
      return { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + localStorage.getItem('token') };
    },

    async send() {
      const title = this.form.title.trim();
      if (!title) return;
      if (!confirm(`Envoyer cette notification à TOUS les propriétaires de la plateforme ?`)) return;

      this.sending = true;
      this.error   = null;
      this.success = null;
      try {
        const res  = await fetch(baseUrl + '/api/admin/notifications/broadcast', {
          method:  'POST',
          headers: this.authHeaders(),
          body:    JSON.stringify({ title, message: this.form.message.trim() }),
        });
        const data = await res.json();
        if (data.success) {
          this.success = data.message;
          this.form = { title: '', message: '' };
        } else {
          this.error = data.message || 'Erreur lors de l\'envoi.';
        }
      } catch (e) {
        this.error = 'Erreur réseau.';
      } finally {
        this.sending = false;
      }
    },
  };
}
