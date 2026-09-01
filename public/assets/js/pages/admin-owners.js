/* ============================================================
   Afristay — Admin plateforme : Propriétaires (src/templates/admin/owners.php)
   ============================================================ */

function adminOwnersPage(baseUrl) {
  return {
    ...saasHelpers,

    loading: true,
    owners: [],
    search: '',
    sortKey: 'created_at',
    sortDir: 'desc',
    ownersPage: 1,

    selected: null,
    ownerEstablishments: [],
    establishmentsLoading: false,
    suspendingId: null,

    async init() {
      await this.loadOwners();
    },

    /** Suspend/réactive la connexion du propriétaire (AuthController::login()) —
        révoque aussi ses sessions actives côté serveur (AdminController::suspendOwner). */
    async toggleSuspend(owner) {
      const suspending = !owner.suspended_at;
      const label = suspending ? 'suspendre' : 'réactiver';
      if (!confirm(`Confirmer : ${label} le compte de « ${owner.name} » ?`)) return;

      this.suspendingId = owner.id;
      try {
        const action = suspending ? 'suspend' : 'unsuspend';
        const res  = await fetch(baseUrl + '/api/admin/owners/' + owner.id + '/' + action, {
          method: 'POST', headers: this.apiHeaders(),
        });
        const data = await res.json();
        if (data.success) {
          owner.suspended_at = suspending ? new Date().toISOString() : null;
          if (this.selected && this.selected.id === owner.id) this.selected.suspended_at = owner.suspended_at;
          this.showToast(suspending ? 'Propriétaire suspendu.' : 'Propriétaire réactivé.', 'success');
        } else {
          this.showToast(data.message ?? 'Erreur.', 'error');
        }
      } catch (e) {
        this.showToast('Erreur réseau.', 'error');
      } finally {
        this.suspendingId = null;
      }
    },

    async loadOwners() {
      this.loading = true;
      try {
        const res  = await fetch(baseUrl + '/api/admin/owners', { headers: this.apiHeaders() });
        const data = await res.json();
        this.owners = data.success ? (data.data ?? []) : [];
      } catch(e) {
        this.owners = [];
      } finally {
        this.loading = false;
      }
    },

    get filteredOwners() {
      const q = (this.search || '').trim().toLowerCase();
      if (!q) return this.owners;
      return this.owners.filter(o => `${o.name} ${o.email}`.toLowerCase().includes(q));
    },

    get sortedOwners() {
      const list = [...this.filteredOwners];
      const key  = this.sortKey;
      const dir  = this.sortDir === 'asc' ? 1 : -1;
      list.sort((a, b) => {
        if (key === 'establishment_count') return (a.establishment_count - b.establishment_count) * dir;
        // created_at : chaînes de date MySQL/ISO, comparables lexicographiquement telles quelles.
        return (a.created_at < b.created_at ? -1 : a.created_at > b.created_at ? 1 : 0) * dir;
      });
      return list;
    },

    get pagedOwners()    { return this.pageItems(this.sortedOwners, this.ownersPage); },
    get ownersPageCount() { return this.pageCount(this.sortedOwners); },

    toggleSort(key) {
      if (this.sortKey === key) {
        this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortKey = key;
        this.sortDir = 'desc';
      }
      this.ownersPage = 1;
    },
    sortIndicator(key) {
      if (this.sortKey !== key) return '';
      return this.sortDir === 'asc' ? '▲' : '▼';
    },

    initials(name) {
      const parts = (name || '').trim().split(/\s+/).filter(Boolean);
      return ((parts[0]?.[0] ?? '') + (parts[1]?.[0] ?? '')).toUpperCase();
    },

    planLabel(p) { return { starter: 'Starter', pro: 'Pro', business: 'Business' }[p] ?? p; },

    async openOwner(owner) {
      this.selected = owner;
      this.ownerEstablishments = [];
      this.establishmentsLoading = true;
      try {
        const res  = await fetch(baseUrl + '/api/admin/owners/' + owner.id + '/establishments', { headers: this.apiHeaders() });
        const data = await res.json();
        this.ownerEstablishments = data.success ? (data.data ?? []) : [];
      } catch(e) {
        this.ownerEstablishments = [];
      } finally {
        this.establishmentsLoading = false;
      }
    },

    // Export client (les données sont déjà en mémoire, respecte la recherche/le tri en cours) —
    // pas de nouvel appel serveur nécessaire pour un simple export de la liste affichée.
    exportCsv() {
      const rows = [['Nom', 'Email', 'Téléphone', 'Établissements', 'Inscrit le', 'Email vérifié']];
      this.sortedOwners.forEach(o => {
        rows.push([
          o.name ?? '',
          o.email ?? '',
          o.phone ?? '',
          String(o.establishment_count ?? 0),
          this.formatDate(o.created_at),
          o.email_verified_at ? 'Oui' : 'Non',
        ]);
      });

      const csv = rows
        .map(row => row.map(field => `"${String(field).replace(/"/g, '""')}"`).join(';'))
        .join('\r\n');

      // BOM UTF-8 : Excel (souvent utilisé côté compta) interprète mal les accents sans lui.
      const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' });
      const url  = URL.createObjectURL(blob);
      const a    = document.createElement('a');
      a.href     = url;
      a.download = `proprietaires-afristay-${new Date().toISOString().slice(0, 10)}.csv`;
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(url);
    },
  };
}
