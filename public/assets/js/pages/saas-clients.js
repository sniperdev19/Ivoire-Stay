/* ============================================================
   Ivoire Stay — Page SaaS : Clients (src/templates/saas/clients.php)
   ============================================================ */

function clientsPage(baseUrl) {
  return {
  // Données
  clients: [],
  loading: true,
  search: '',

  // Détail
  showDetail: false,
  selectedClient: null,
  clientHistory: [],
  detailLoading: false,

  // Edition
  showEdit: false,
  editForm: { name:'', email:'', phone:'', address:'' },
  editSaving: false,
  editError: null,

  // Toast
  toast: null,
  toastTimer: null,

  // Fallback (12 clients fictifs)
  fallbackClients: [
    { id:1, name:'Kouamé Adou', email:'kouame@email.ci', phone:'+225 07 11 22 33', address:'Cocody, Abidjan', total_bookings:5, total_spent:1250000, last_visit:'2026-06-14', created_at:'2026-01-10' },
    { id:2, name:'Fatou Diallo', email:'fatou@email.ci', phone:'+225 05 44 55 66', address:'Plateau, Abidjan', total_bookings:3, total_spent:480000, last_visit:'2026-06-14', created_at:'2026-02-05' },
    { id:3, name:'Marc Koffi', email:'marc@email.ci', phone:'+225 01 77 88 99', address:'Yopougon, Abidjan', total_bookings:8, total_spent:2100000, last_visit:'2026-06-12', created_at:'2025-11-20' },
    { id:4, name:'Awa Traoré', email:'awa@email.ci', phone:'+225 07 22 33 44', address:'Marcory, Abidjan', total_bookings:2, total_spent:380000, last_visit:'2026-06-15', created_at:'2026-04-18' },
    { id:5, name:'Yao Brou', email:'yao@email.ci', phone:'+225 05 55 66 77', address:'Treichville, Abidjan', total_bookings:6, total_spent:1680000, last_visit:'2026-06-14', created_at:'2026-01-30' },
    { id:6, name:'Amina Coulibaly', email:'amina@email.ci', phone:'+225 01 33 44 55', address:'Abobo, Abidjan', total_bookings:1, total_spent:55000, last_visit:'2026-06-10', created_at:'2026-06-08' },
    { id:7, name:'Jean Ouattara', email:'jean@email.ci', phone:'+225 07 88 99 00', address:'Angré, Abidjan', total_bookings:4, total_spent:920000, last_visit:'2026-05-28', created_at:'2026-03-12' },
    { id:8, name:'Marie Gbagbo', email:'marie@email.ci', phone:'+225 05 11 00 99', address:'Riviera, Abidjan', total_bookings:2, total_spent:290000, last_visit:'2026-05-15', created_at:'2026-05-01' },
    { id:9, name:'Olivier Mensah', email:'olivier@email.ci', phone:'+225 07 99 11 22', address:'Koumassi, Abidjan', total_bookings:0, total_spent:0, last_visit:null, created_at:'2026-03-01' },
    { id:10, name:'Sita Koné', email:'sita@email.ci', phone:'+225 01 44 55 66', address:'Adjamé, Abidjan', total_bookings:2, total_spent:210000, last_visit:'2026-04-12', created_at:'2026-02-20' },
    { id:11, name:'Pauline Zongo', email:'pauline@email.ci', phone:'+225 05 22 33 44', address:'Zone 4, Abidjan', total_bookings:1, total_spent:60000, last_visit:'2026-06-01', created_at:'2026-05-05' },
    { id:12, name:'Eric N’Guessan', email:'eric@email.ci', phone:'+225 07 66 55 44', address:'Bingerville', total_bookings:7, total_spent:1320000, last_visit:'2026-05-30', created_at:'2025-12-12' }
  ],

  fallbackHistory: [
    { id:1, room_name:'Suite Présidentielle', check_in:'2026-06-14', check_out:'2026-06-16', nights:2, total_price:640000, status:'confirmed' },
    { id:2, room_name:'Chambre Deluxe', check_in:'2026-05-10', check_out:'2026-05-11', nights:1, total_price:95000, status:'checked_out' },
    { id:3, room_name:'Studio Standard', check_in:'2026-04-02', check_out:'2026-04-03', nights:1, total_price:55000, status:'checked_out' }
  ],

  // Init
  async init() { await this.loadClients(); },

  apiBase: baseUrl + '',
  apiUrl(path) { return this.apiBase + path; },
  apiHeaders() { return { 'Content-Type':'application/json', 'Authorization':'Bearer ' + localStorage.getItem('token') }; },
  estId() { return localStorage.getItem('establishment_id') || '1'; },

  // Charger clients
  async loadClients() {
    this.loading = true;
    try {
      const url = this.apiUrl('/api/clients?establishment_id=' + this.estId()) + (this.search ? '&search=' + encodeURIComponent(this.search) : '');
      const res = await fetch(url, { headers: this.apiHeaders() });
      const data = await res.json();
      const clients = data.success ? (data.data?.clients ?? data.data ?? []) : [];
      this.clients = clients.map(c => ({
        ...c,
        name: (c.name ?? [c.first_name, c.last_name].filter(Boolean).join(' ')) || 'Client',
        total_bookings: c.total_bookings ?? c.booking_count ?? 0,
        total_spent: c.total_spent ?? 0,
        last_visit: c.last_visit ?? c.last_booking ?? c.updated_at ?? null,
      }));
      if (!this.clients.length) this.clients = this.fallbackClients;
    } catch(e) {
      this.clients = this.fallbackClients;
    } finally {
      this.loading = false;
    }
  },

  // Recherche
  async doSearch() { await this.loadClients(); },

  // Filtre local
  get filteredClients() {
    if (!this.search) return this.clients;
    const q = this.search.toLowerCase();
    return this.clients.filter(c => (c.name||'').toLowerCase().includes(q) || (c.email||'').toLowerCase().includes(q) || (c.phone||'').includes(q));
  },

  // Ouvrir détail (charge détail + historique)
  async openDetail(client) {
    this.selectedClient = { ...client };
    if (!this.selectedClient.name && (this.selectedClient.first_name || this.selectedClient.last_name)) {
      this.selectedClient.name = [this.selectedClient.first_name, this.selectedClient.last_name].filter(Boolean).join(' ');
    }
    this.clientHistory = [];
    this.showDetail = true;
    this.detailLoading = true;
    try {
      const res = await fetch(this.apiUrl('/api/clients/' + client.id), { headers: this.apiHeaders() });
      const data = await res.json();
      if (data.success) {
        const c = data.data?.client ?? data.data ?? client;
        if (!c.name && (c.first_name || c.last_name)) {
          c.name = [c.first_name, c.last_name].filter(Boolean).join(' ');
        }
        this.selectedClient = c;
        this.clientHistory = data.data?.bookings ?? data.data?.history ?? [];
      } else {
        this.clientHistory = this.fallbackHistory;
      }
    } catch(e) {
      this.clientHistory = this.fallbackHistory;
    } finally {
      this.detailLoading = false;
    }
  },

  // Ouvrir édition (s'ouvre par-dessus le détail)
  openEdit(client) {
    this.editForm = { name: client.name ?? '', email: client.email ?? '', phone: client.phone ?? '', address: client.address ?? '' };
    this.editError = null;
    this.showEdit = true;
  },

  // Sauvegarder édition (mise à jour locale, les modals restent ouverts)
  async saveEdit() {
    this.editError = null;
    if (!this.editForm.name?.trim()) { this.editError = 'Le nom du client est obligatoire.'; return; }
    this.editSaving = true;
    try {
      const res = await fetch(this.apiUrl('/api/clients/' + this.selectedClient.id), { method:'PUT', headers: this.apiHeaders(), body: JSON.stringify(this.editForm) });
      const data = await res.json();
      if (data.success) {
        Object.assign(this.selectedClient, this.editForm);
        const idx = this.clients.findIndex(c => c.id === this.selectedClient.id);
        if (idx !== -1) Object.assign(this.clients[idx], this.editForm);
        this.showToast('Client mis à jour.', 'success');
        // Ne pas fermer les modals — conformément à la contrainte
      } else {
        this.editError = data.message ?? 'Erreur lors de la mise à jour.';
      }
    } catch(e) {
      this.editError = 'Erreur réseau.';
    } finally {
      this.editSaving = false;
    }
  },

  // Supprimer client
  async deleteClient(id) {
    if (!confirm('Supprimer ce client ? Ses réservations resteront enregistrées.')) return;
    try {
      const res = await fetch(this.apiUrl('/api/clients/' + id), { method:'DELETE', headers: this.apiHeaders() });
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

  // Helpers
  formatPrice(p) { return new Intl.NumberFormat('fr-FR').format(p ?? 0) + ' FCFA'; },
  formatDate(d) { if (!d) return '-'; return new Date(d).toLocaleDateString('fr-FR', { day:'numeric', month:'short', year:'numeric' }); },
  initials(name) { const parts = (name||'?').split(' ').filter(Boolean); return (parts[0]?.[0]??'') + (parts[1]?.[0]??''); },
  avatarBg(id) { const colors = [ 'linear-gradient(135deg,#C9A84C,#A67C2E)', 'linear-gradient(135deg,#2563EB,#1D4ED8)', 'linear-gradient(135deg,#16a34a,#15803D)', 'linear-gradient(135deg,#7C3AED,#6D28D9)', 'linear-gradient(135deg,#DC2626,#B91C1C)' ]; return colors[(id??0) % colors.length]; },
  statusCfg(s) { return { confirmed:{label:'Confirmée', badge:'badge badge-success'}, pending:{label:'En attente', badge:'badge badge-warning'}, checked_in:{label:'Arrivée', badge:'badge badge-info'}, checked_out:{label:'Départ', badge:'badge badge-gold'}, cancelled:{label:'Annulée', badge:'badge badge-danger'} }[s] ?? { label: s, badge:'badge' }; },

  // Toast
  showToast(msg, type='success') { this.toast = { msg, type }; clearTimeout(this.toastTimer); this.toastTimer = setTimeout(() => { this.toast = null; }, 3500); }
  };
}
