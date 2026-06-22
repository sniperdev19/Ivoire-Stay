/* ============================================================
   Ivoire Stay — Page tarification (vitrine/pricing.php)
   Toggle annuel/mensuel + accordéon FAQ
   ============================================================ */

function pricingPage() {
  return {
    annual: false,
    open: null,

    toggle() { this.annual = !this.annual; },

    toggleFaq(idx) { this.open = this.open === idx ? null : idx; },
  };
}
