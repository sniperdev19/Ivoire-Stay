/* ============================================================
   Afristay — Admin plateforme : Annonces vitrine
   (src/templates/admin/announcements.php)
   ============================================================ */

function adminAnnouncementsPage(baseUrl) {
  return {
    loading:       true,
    announcements: [],
    form:          { title: '', message: '' },
    creating:      false,
    error:         null,

    async init() {
      await this.loadAnnouncements();
    },

    authHeaders() {
      return { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + localStorage.getItem('token') };
    },

    async loadAnnouncements() {
      this.loading = true;
      try {
        const res  = await fetch(baseUrl + '/api/admin/announcements', { headers: this.authHeaders() });
        const data = await res.json();
        this.announcements = data.success ? (data.data ?? []) : [];
      } catch (e) {
        this.announcements = [];
      } finally {
        this.loading = false;
      }
    },

    async create() {
      this.creating = true;
      this.error    = null;
      try {
        const res  = await fetch(baseUrl + '/api/admin/announcements', {
          method:  'POST',
          headers: this.authHeaders(),
          body:    JSON.stringify(this.form),
        });
        const data = await res.json();
        if (data.success) {
          this.form = { title: '', message: '' };
          await this.loadAnnouncements();
        } else {
          this.error = data.message || 'Erreur lors de la création.';
        }
      } catch (e) {
        this.error = 'Erreur réseau.';
      } finally {
        this.creating = false;
      }
    },

    async toggleActive(a) {
      const next = a.is_active == 1 ? 0 : 1;
      try {
        const res  = await fetch(baseUrl + '/api/admin/announcements/' + a.id, {
          method: 'PUT', headers: this.authHeaders(), body: JSON.stringify({ is_active: next }),
        });
        const data = await res.json();
        if (data.success) await this.loadAnnouncements();
      } catch (e) { /* silencieux */ }
    },

    async deleteAnnouncement(a) {
      if (!confirm(`Supprimer l'annonce « ${a.title} » ?`)) return;
      try {
        const res  = await fetch(baseUrl + '/api/admin/announcements/' + a.id, {
          method: 'DELETE', headers: this.authHeaders(),
        });
        const data = await res.json();
        if (data.success) this.announcements = this.announcements.filter(x => x.id !== a.id);
      } catch (e) { /* silencieux */ }
    },

    formatDate(d) {
      if (!d) return '-';
      return new Date(d).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
    },
  };
}
