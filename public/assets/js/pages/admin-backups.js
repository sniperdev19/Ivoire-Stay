/* ============================================================
   Afristay — Admin plateforme : Sauvegardes (src/templates/admin/backups.php)
   ============================================================ */

function adminBackupsPage(baseUrl) {
  return {
    ...saasHelpers,

    loading: true,
    creating: false,
    backups: [],
    toast: null,

    async init() {
      await this.loadBackups();
    },

    async loadBackups() {
      this.loading = true;
      try {
        const res  = await fetch(baseUrl + '/api/admin/backups', { headers: this.apiHeaders() });
        const data = await res.json();
        this.backups = data.success ? (data.data ?? []) : [];
      } catch (e) {
        this.backups = [];
      } finally {
        this.loading = false;
      }
    },

    async createBackup() {
      this.creating = true;
      try {
        const res  = await fetch(baseUrl + '/api/admin/backups', { method: 'POST', headers: this.apiHeaders() });
        const data = await res.json();
        if (data.success) {
          await this.loadBackups();
          this.showToast('Sauvegarde créée.', 'success');
        } else {
          this.showToast(data.message ?? 'Erreur lors de la sauvegarde.', 'error');
        }
      } catch (e) {
        this.showToast('Erreur réseau.', 'error');
      } finally {
        this.creating = false;
      }
    },

    /* Téléchargement authentifié : le token est un Bearer en header, pas un
       cookie — un <a href> brut ne l'enverrait pas, il faut passer par
       fetch + blob (même pattern que le téléchargement PDF, saas-billing.js). */
    async downloadBackup(b) {
      try {
        const res = await fetch(baseUrl + '/api/admin/backups/' + b.filename + '/download', {
          headers: this.apiHeaders(),
        });
        if (!res.ok) { this.showToast('Erreur lors du téléchargement.', 'error'); return; }
        const blob = await res.blob();
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = b.filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
      } catch (e) {
        this.showToast('Erreur réseau lors du téléchargement.', 'error');
      }
    },

    formatDateTime(d) {
      if (!d) return '-';
      // created_at vient au format 'Y-m-d H:i:s' (MySQL-like) — Safari ne parse pas
      // cet espace comme séparateur date/heure, remplacer par 'T' pour rester portable.
      return new Date(d.replace(' ', 'T')).toLocaleString('fr-FR', {
        day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
      });
    },

    formatSize(bytes) {
      if (!bytes) return '0 o';
      if (bytes < 1024) return bytes + ' o';
      if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' Ko';
      return (bytes / 1048576).toFixed(1) + ' Mo';
    },
  };
}
