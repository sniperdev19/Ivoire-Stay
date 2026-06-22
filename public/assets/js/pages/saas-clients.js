/* ============================================================
   Ivoire Stay — Page SaaS : Clients (src/templates/saas/clients.php)
   ============================================================ */

function clientsPage(baseUrl) {
  return {
  ...saasHelpers,

  clients: [],
  loading: true,
  search: '',

  showDetail: false,
  selectedClient: null,
  clientHistory: [],
  detailLoading: false,

  showEdit: false,
  editForm: { first_name: '', last_name: '', email: '', phone: '' },
  editSaving: false,
  editError: null,

  async init() { await this.loadClients(); },

  apiBase: baseUrl + '',
  apiUrl(path) { return this.apiBase + path; },

  async loadClients() {
    this.loading = true;
    try {
      const url  = this.apiUrl('/api/clients?establishment_id=' + this.estId())
        + (this.search ? '&search=' + encodeURIComponent(this.search) : '');
      const res  = await fetch(url, { headers: this.apiHeaders() });
      const data = await res.json();
      const raw  = data.success ? (data.data?.clients ?? data.data ?? []) : [];
      this.clients = raw.map(c => ({
        ...c,
        name: (c.name ?? [c.first_name, c.last_name].filter(Boolean).join(' ')) || 'Client',
        total_bookings: c.total_bookings ?? c.booking_count ?? 0,
        total_spent:    c.total_spent ?? 0,
        last_visit:     c.last_visit ?? c.last_booking ?? c.updated_at ?? null,
      }));
    } catch(e) {
      this.clients = [];
    } finally {
      this.loading = false;
    }
  },

  async doSearch() { await this.loadClients(); },

  /* Le serveur filtre déjà sur this.search — pas de double-passe côté client */
  get filteredClients() { return this.clients; },

  async openDetail(client) {
    this.selectedClient = { ...client };
    if (!this.selectedClient.name && (this.selectedClient.first_name || this.selectedClient.last_name)) {
      this.selectedClient.name = [this.selectedClient.first_name, this.selectedClient.last_name].filter(Boolean).join(' ');
    }
    this.clientHistory = [];
    this.showDetail    = true;
    this.detailLoading = true;
    try {
      const res  = await fetch(this.apiUrl('/api/clients/' + client.id), { headers: this.apiHeaders() });
      const data = await res.json();
      if (data.success) {
        const c = data.data?.client ?? data.data ?? client;
        if (!c.name && (c.first_name || c.last_name)) {
          c.name = [c.first_name, c.last_name].filter(Boolean).join(' ');
        }
        this.selectedClient = c;
        this.clientHistory  = data.data?.bookings ?? data.data?.history ?? [];
      }
    } catch(e) {
      this.clientHistory = [];
    } finally {
      this.detailLoading = false;
    }
  },

  openEdit(client) {
    // Décomposer le nom complet en prénom / nom si besoin
    const nameParts = (client.name ?? '').trim().split(/\s+/);
    this.editForm = {
      first_name: client.first_name ?? nameParts[0] ?? '',
      last_name:  client.last_name  ?? nameParts.slice(1).join(' ') ?? '',
      email:      client.email      ?? '',
      phone:      client.phone      ?? '',
    };
    this.editError = null;
    this.showEdit  = true;
  },

  async saveEdit() {
    this.editError = null;
    if (!this.editForm.first_name?.trim()) { this.editError = 'Le prénom du client est obligatoire.'; return; }
    this.editSaving = true;
    try {
      const res  = await fetch(this.apiUrl('/api/clients/' + this.selectedClient.id), {
        method: 'PUT', headers: this.apiHeaders(), body: JSON.stringify(this.editForm)
      });
      const data = await res.json();
      if (data.success) {
        const updatedName = [this.editForm.first_name, this.editForm.last_name].filter(Boolean).join(' ');
        Object.assign(this.selectedClient, { ...this.editForm, name: updatedName });
        const idx = this.clients.findIndex(c => c.id === this.selectedClient.id);
        if (idx !== -1) Object.assign(this.clients[idx], { ...this.editForm, name: updatedName });
        this.showToast('Client mis à jour.', 'success');
      } else {
        this.editError = data.message ?? 'Erreur lors de la mise à jour.';
      }
    } catch(e) {
      this.editError = 'Erreur réseau.';
    } finally {
      this.editSaving = false;
    }
  },

  async deleteClient(id) {
    if (!confirm('Supprimer ce client ? Ses réservations resteront enregistrées.')) return;
    try {
      const res  = await fetch(this.apiUrl('/api/clients/' + id), { method: 'DELETE', headers: this.apiHeaders() });
      const data = await res.json();
      if (data.success) {
        this.clients = this.clients.filter(c => c.id !== id);
        this.showDetail = false;
        this.showToast('Client supprimé.', 'success');
      } else {
        this.showToast(data.message ?? 'Erreur.', 'error');
      }
    } catch(e) {
      this.showToast('Erreur réseau.', 'error');
    }
  },

  initials(name) {
    const parts = (name || '?').split(' ').filter(Boolean);
    return (parts[0]?.[0] ?? '') + (parts[1]?.[0] ?? '');
  },
  avatarBg(id) {
    const colors = [
      'linear-gradient(135deg,#C9A84C,#A67C2E)',
      'linear-gradient(135deg,#2563EB,#1D4ED8)',
      'linear-gradient(135deg,#16a34a,#15803D)',
      'linear-gradient(135deg,#7C3AED,#6D28D9)',
      'linear-gradient(135deg,#DC2626,#B91C1C)',
    ];
    return colors[(id ?? 0) % colors.length];
  },
  statusCfg(s) { return BOOKING_STATUS[s] ?? { label: s, badge: 'badge' }; },
  };
}
