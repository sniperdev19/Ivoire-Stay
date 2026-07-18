/* ============================================================
   Afristay — Page contact (vitrine/contact.php)
   Formulaire de contact + accordéon FAQ
   ============================================================ */

function contactPage(base) {
  base = (base || '').replace(/\/$/, '');
  return {
    sent: false,
    sending: false,
    sendError: null,
    open: null,
    form: {
      name:    '',
      email:   '',
      phone:   '',
      subject: 'Question générale',
      message: '',
    },

    async sendMessage() {
      if (this.sending) return;
      this.sendError = null;
      this.sending = true;
      try {
        const res = await fetch(base + '/api/public/contact', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(this.form),
        });
        const data = await res.json();
        if (data.success) {
          this.sent = true;
        } else {
          this.sendError = data.message || 'Impossible d\'envoyer le message.';
        }
      } catch (e) {
        this.sendError = 'Erreur réseau. Vérifiez votre connexion et réessayez.';
      } finally {
        this.sending = false;
      }
    },

    toggleFaq(idx) { this.open = this.open === idx ? null : idx; },
  };
}
