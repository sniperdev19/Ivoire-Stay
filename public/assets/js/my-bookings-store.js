/* ============================================================
   Afristay — Historique local des réservations "voyageur"
   Persiste booking_id + guest_token sur cet appareil/navigateur pour
   permettre à /mes-reservations de retrouver rapidement une réservation
   sans repasser par la recherche email+téléphone. Chargé globalement dans
   vitrine/layout.php (utilisé par booking.js à la confirmation et par
   mes-reservations.js à la lecture).
   ============================================================ */
window.AfristayMyBookings = (function () {
  const KEY = 'afristay_my_bookings';
  const MAX = 20;

  function list() {
    try {
      const raw = localStorage.getItem(KEY);
      const items = raw ? JSON.parse(raw) : [];
      return Array.isArray(items) ? items : [];
    } catch (e) {
      return [];
    }
  }

  function add(booking) {
    const id = booking && (booking.id ?? booking.booking_id);
    const token = booking && booking.guest_token;
    if (!id || !token) return;
    try {
      const items = list().filter((b) => b.id !== id);
      items.unshift({ id, guest_token: token, saved_at: Date.now() });
      localStorage.setItem(KEY, JSON.stringify(items.slice(0, MAX)));
    } catch (e) {
      // localStorage indisponible (navigation privée stricte, quota) — best-effort
    }
  }

  return { list, add };
})();
