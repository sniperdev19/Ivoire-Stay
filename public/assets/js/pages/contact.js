/* ============================================================
   Ivoire Stay — Page contact (vitrine/contact.php)
   Formulaire de contact + accordéon FAQ
   ============================================================ */

function contactPage() {
  return {
    sent: false,
    open: null,

    submit() { this.sent = true; },

    toggleFaq(idx) { this.open = this.open === idx ? null : idx; },
  };
}
