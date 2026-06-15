<?php ?>
<style>
/* Ligne client cliquable */
.client-row {
  cursor: pointer;
  transition: background 0.15s;
}
.client-row:hover td { background: rgba(201,168,76,0.03) !important; }
/* Avatar couleur */
.client-avatar {
  width: 36px; height: 36px; border-radius: 10px;
  display: grid; place-items: center;
  font-size: 13px; font-weight: 700;
  color: white; flex-shrink: 0;
}
/* Stat client dans modal */
.client-stat { background: #FAFAFA; border-radius: 12px; padding: 14px; text-align:center; }
.client-stat-value { font-size:20px; font-weight:800; color:#111827; line-height:1; margin-bottom:4px; }
.client-stat-label { font-size:11px; color:#9CA3AF; text-transform:uppercase; letter-spacing:0.05em; }
@keyframes spin { to { transform: rotate(360deg); } }
@keyframes shimmer { 0% { background-position:200% 0 } 100% { background-position:-200% 0 } }
.cl-shimmer { background: linear-gradient(90deg,#f8f6f2 25%,#f0ebe1 50%,#f8f6f2 75%); background-size:400% 100%; animation: shimmer 1.4s linear infinite; border-radius:8px; }
</style>

<!--
  Template clients SaaS
  Injecté dans saas/layout.php via $content
  Variables PHP disponibles : $title, $page, $base_url
-->

<div x-data="{
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

  apiBase: '<?= rtrim($base_url, '/') ?>',
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
        name: c.name ?? [c.first_name, c.last_name].filter(Boolean).join(' ') || 'Client',
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
}"
 x-init="init()">

  <!-- En-tête et stats -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
      <h1 style="font-size:20px;font-weight:700;color:#111827;margin:0 0 4px;">Clients</h1>
      <p style="font-size:13px;color:#9CA3AF;margin:0;"><span x-text="clients.length"></span> client(s) enregistré(s)</p>
    </div>
  </div>

  <!-- KPI compactes -->
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px;">
    <div class="saas-card" style="padding:14px 16px;border-top:3px solid #C9A84C;">
      <div style="font-size:11px;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px;">Total clients</div>
      <div style="font-size:24px;font-weight:800;color:#111827;" x-text="clients.length"></div>
    </div>
    <div class="saas-card" style="padding:14px 16px;border-top:3px solid #16a34a;">
      <div style="font-size:11px;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px;">Avec réservations</div>
      <div style="font-size:24px;font-weight:800;color:#16a34a;" x-text="clients.filter(c => (c.total_bookings ?? 0) > 0).length"></div>
    </div>
    <div class="saas-card" style="padding:14px 16px;border-top:3px solid #2563EB;">
      <div style="font-size:11px;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px;">Revenu total clients</div>
      <div style="font-size:18px;font-weight:800;color:#2563EB;" x-text="formatPrice(clients.reduce((sum,c) => sum + (c.total_spent ?? 0), 0))"></div>
    </div>
  </div>

  <!-- Barre recherche -->
  <div class="saas-card" style="padding:14px 16px;margin-bottom:16px;">
    <div style="display:flex;gap:10px;align-items:center;">
      <div style="position:relative;flex:1;">
        <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9CA3AF;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" class="saas-input" placeholder="Rechercher par nom, email, téléphone..." x-model="search" @input.debounce.350ms="doSearch()" style="padding-left:34px;" />
      </div>
      <button x-show="search" @click="search=''; loadClients()" class="btn-saas-secondary" style="white-space:nowrap;">
        <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        Effacer
      </button>
    </div>
  </div>

  <!-- Etat loading -->
  <div x-show="loading">
    <div class="saas-card" style="padding:0;overflow:hidden;">
      <div style="height:44px;background:#FAFAFA;border-bottom:1px solid rgba(0,0,0,0.05);"></div>
      <template x-for="i in [1,2,3,4,5,6]" :key="i">
        <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid rgba(0,0,0,0.04);">
          <div class="cl-shimmer" style="width:36px;height:36px;border-radius:10px;flex-shrink:0;"></div>
          <div style="flex:1;display:flex;flex-direction:column;gap:6px;"><div class="cl-shimmer" style="height:13px;width:35%;"></div><div class="cl-shimmer" style="height:11px;width:22%;"></div></div>
          <div class="cl-shimmer" style="height:13px;width:80px;"></div>
          <div class="cl-shimmer" style="height:13px;width:60px;"></div>
        </div>
      </template>
    </div>
  </div>

  <!-- Table clients -->
  <div x-show="!loading">

    <!-- Vide -->
    <div x-show="filteredClients.length === 0" class="saas-card" style="text-align:center;padding:56px;">
      <div style="width:52px;height:52px;border-radius:14px;background:rgba(201,168,76,0.08);border:1px solid rgba(201,168,76,0.15);display:grid;place-items:center;margin:0 auto 14px;">
        <svg xmlns="http://www.w3.org/2000/svg" style="width:22px;height:22px;color:#C9A84C;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      </div>
      <h3 style="font-size:15px;font-weight:600;color:#111827;margin:0 0 6px;">Aucun client trouvé</h3>
      <p style="font-size:13px;color:#9CA3AF;margin:0 0 18px;">Essayez un autre terme de recherche.</p>
      <button @click="search=''; loadClients()" class="btn-saas-secondary">Voir tous les clients</button>
    </div>

    <!-- Table -->
    <div x-show="filteredClients.length > 0" class="saas-card" style="padding:0;overflow:hidden;">
      <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
          <thead>
            <tr style="background:#FAFAFA;">
              <th style="text-align:left;font-size:11px;font-weight:600;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.05em;padding:12px 20px;border-bottom:1px solid rgba(0,0,0,0.06);">Client</th>
              <th style="text-align:left;font-size:11px;font-weight:600;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.05em;padding:12px 16px;border-bottom:1px solid rgba(0,0,0,0.06);">Contact</th>
              <th style="text-align:center;font-size:11px;font-weight:600;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.05em;padding:12px 16px;border-bottom:1px solid rgba(0,0,0,0.06);">Réservations</th>
              <th style="text-align:right;font-size:11px;font-weight:600;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.05em;padding:12px 16px;border-bottom:1px solid rgba(0,0,0,0.06);">Total dépensé</th>
              <th style="text-align:left;font-size:11px;font-weight:600;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.05em;padding:12px 16px;border-bottom:1px solid rgba(0,0,0,0.06);">Dernière visite</th>
              <th style="padding:12px 16px;border-bottom:1px solid rgba(0,0,0,0.06);"></th>
            </tr>
          </thead>
          <tbody>
            <template x-for="client in filteredClients" :key="client.id">
              <tr class="client-row" @click="openDetail(client)">
                <td style="padding:13px 20px;border-bottom:1px solid rgba(0,0,0,0.04);">
                  <div style="display:flex;align-items:center;gap:10px;">
                    <div class="client-avatar" :style="'background:' + avatarBg(client.id)" x-text="initials(client.name)"></div>
                    <div>
                      <div style="font-size:14px;font-weight:600;color:#111827;line-height:1.2;" x-text="client.name"></div>
                      <div style="font-size:12px;color:#9CA3AF;" x-text="client.address ?? ''"></div>
                    </div>
                  </div>
                </td>
                <td style="padding:13px 16px;border-bottom:1px solid rgba(0,0,0,0.04);">
                  <div style="font-size:13px;color:#374151;" x-text="client.phone ?? '-'"></div>
                  <div style="font-size:12px;color:#9CA3AF;" x-text="client.email ?? ''"></div>
                </td>
                <td style="padding:13px 16px;text-align:center;border-bottom:1px solid rgba(0,0,0,0.04);">
                  <div style="display:inline-flex;align-items:center;gap:5px;"><div style="width:24px;height:24px;border-radius:6px;background:rgba(201,168,76,0.1);display:grid;place-items:center;"><svg xmlns="http://www.w3.org/2000/svg" style="width:12px;height:12px;color:#C9A84C;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg></div><span style="font-size:15px;font-weight:700;color:#111827;" x-text="client.total_bookings ?? 0"></span></div>
                </td>
                <td style="padding:13px 16px;text-align:right;border-bottom:1px solid rgba(0,0,0,0.04);"><span style="font-size:14px;font-weight:600;color:#111827;" x-text="formatPrice(client.total_spent ?? 0)"></span></td>
                <td style="padding:13px 16px;border-bottom:1px solid rgba(0,0,0,0.04);"><span style="font-size:13px;color:#6B7280;" x-text="formatDate(client.last_visit ?? client.updated_at)"></span></td>
                <td style="padding:13px 16px;border-bottom:1px solid rgba(0,0,0,0.04);" @click.stop>
                  <button @click="openDetail(client)" style="width:30px;height:30px;border-radius:8px;background:rgba(0,0,0,0.04);border:none;cursor:pointer;display:grid;place-items:center;transition:all 0.2s;" onmouseover="this.style.background='rgba(201,168,76,0.1)'" onmouseout="this.style.background='rgba(0,0,0,0.04)'">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;color:#6B7280;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  </button>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Footer table -->
      <div style="padding:12px 20px;background:#FAFAFA;border-top:1px solid rgba(0,0,0,0.05);font-size:12px;color:#9CA3AF;">
        <span x-text="filteredClients.length"></span> client(s) affiché(s)
        <span x-show="search"> · Recherche : "<span x-text="search" style="color:#374151;font-weight:500;"></span>"</span>
      </div>
    </div>
  </div>

  <!-- Modal détail client -->
  <div x-show="showDetail" class="saas-modal-bg" @keydown.escape.window="showDetail = false" @click.self="showDetail = false">
    <div class="saas-modal" style="max-width:620px;" @click.stop>
      <div class="saas-modal-header">
        <div style="display:flex;align-items:center;gap:12px;">
          <div class="client-avatar" style="width:44px;height:44px;border-radius:12px;font-size:16px;" :style="'background:' + avatarBg(selectedClient?.id)" x-text="initials(selectedClient?.name ?? '')"></div>
          <div>
            <h2 style="font-size:17px;font-weight:700;color:#111827;margin:0 0 2px;" x-text="selectedClient?.name ?? 'Client'"></h2>
            <div style="font-size:12px;color:#9CA3AF;">Client depuis <span x-text="formatDate(selectedClient?.created_at)"></span></div>
          </div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
          <button @click="openEdit(selectedClient)" class="btn-saas-secondary" style="font-size:13px;padding:7px 14px;"><svg xmlns="http://www.w3.org/2000/svg" style="width:13px;height:13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg> Modifier</button>
          <button @click="showDetail = false" style="width:32px;height:32px;border-radius:8px;background:rgba(0,0,0,0.05);border:none;cursor:pointer;display:grid;place-items:center;"><svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;color:#6B7280;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
      </div>

      <div class="saas-modal-body" style="padding:0 24px 24px;">
        <div x-show="detailLoading" style="text-align:center;padding:32px;"><div style="width:28px;height:28px;border-radius:50%;border:3px solid rgba(201,168,76,0.2);border-top-color:#C9A84C;animation:spin 0.8s linear infinite;margin:0 auto;"></div></div>

        <div x-show="!detailLoading && selectedClient">
          <!-- 3 stats -->
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:20px;">
            <div class="client-stat"><div class="client-stat-value" x-text="selectedClient?.total_bookings ?? clientHistory.length"></div><div class="client-stat-label">Séjours</div></div>
            <div class="client-stat"><div class="client-stat-value" style="font-size:15px;" x-text="formatPrice(selectedClient?.total_spent ?? clientHistory.reduce((s,b)=> s + (b.total_price ?? 0),0))"></div><div class="client-stat-label">Total dépensé</div></div>
            <div class="client-stat"><div class="client-stat-value" style="font-size:13px;" x-text="formatDate(selectedClient?.last_visit ?? selectedClient?.updated_at)"></div><div class="client-stat-label">Dernière visite</div></div>
          </div>

          <!-- Contact -->
          <div style="background:#FAFAFA;border-radius:12px;padding:16px;margin-bottom:20px;">
            <div style="font-size:11px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:12px;">Informations de contact</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
              <div style="display:flex;align-items:center;gap:10px;"><div style="width:32px;height:32px;border-radius:8px;background:rgba(22,163,74,0.08);display:grid;place-items:center;flex-shrink:0;"><svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;color:#16a34a;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg></div><div><div style="font-size:11px;color:#9CA3AF;">Téléphone</div><div style="font-size:14px;color:#111827;font-weight:500;" x-text="selectedClient?.phone ?? '-'" /></div></div>
              <div style="display:flex;align-items:center;gap:10px;"><div style="width:32px;height:32px;border-radius:8px;background:rgba(37,99,235,0.08);display:grid;place-items:center;flex-shrink:0;"><svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;color:#2563EB;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></div><div><div style="font-size:11px;color:#9CA3AF;">Email</div><div style="font-size:14px;color:#111827;font-weight:500;" x-text="selectedClient?.email ?? '-'" /></div></div>
              <div style="display:flex;align-items:center;gap:10px;grid-column:span 2;"><div style="width:32px;height:32px;border-radius:8px;background:rgba(201,168,76,0.08);display:grid;place-items:center;flex-shrink:0;"><svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;color:#C9A84C;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div><div><div style="font-size:11px;color:#9CA3AF;">Adresse</div><div style="font-size:14px;color:#111827;font-weight:500;" x-text="selectedClient?.address ?? '-'" /></div></div>
            </div>
          </div>

          <!-- Historique -->
          <div>
            <div style="font-size:13px;font-weight:600;color:#111827;margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;"><span>Historique des séjours</span><span style="font-size:12px;font-weight:400;color:#9CA3AF;" x-text="clientHistory.length + ' séjour(s)'"></span></div>

            <div x-show="clientHistory.length === 0" style="text-align:center;padding:24px;background:#FAFAFA;border-radius:12px;font-size:13px;color:#9CA3AF;">Aucun séjour enregistré</div>

            <div x-show="clientHistory.length > 0" style="display:flex;flex-direction:column;gap:8px;">
              <template x-for="b in clientHistory" :key="b.id">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;background:#FAFAFA;border-radius:10px;border:1px solid rgba(0,0,0,0.05);flex-wrap:wrap;gap:8px;">
                  <div style="display:flex;align-items:center;gap:10px;flex:1;"><div style="width:32px;height:32px;border-radius:8px;background:rgba(201,168,76,0.1);display:grid;place-items:center;flex-shrink:0;"><svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;color:#C9A84C;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg></div><div><div style="font-size:13px;font-weight:500;color:#111827;" x-text="b.room_name ?? '-'"></div><div style="font-size:12px;color:#9CA3AF;"><span x-text="formatDate(b.check_in)"></span> → <span x-text="formatDate(b.check_out)"></span> · <span x-text="(b.nights ?? 1) + ' nuit(s)'"></span></div></div></div>
                  <div style="display:flex;align-items:center;gap:10px;"><span style="font-size:13px;font-weight:600;color:#111827;" x-text="formatPrice(b.total_price ?? b.amount)"></span><span :class="statusCfg(b.status).badge" x-text="statusCfg(b.status).label"></span></div>
                </div>
              </template>
            </div>
          </div>
        </div>
      </div>

      <div class="saas-modal-footer">
        <button @click="deleteClient(selectedClient?.id)" class="btn-saas-danger"><svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg> Supprimer</button>
        <div style="flex:1;"></div>
        <button @click="showDetail = false" class="btn-saas-secondary">Fermer</button>
      </div>
    </div>
  </div>

  <!-- Modal édition client -->
  <div x-show="showEdit" class="saas-modal-bg" @keydown.escape.window="showEdit = false" @click.self="showEdit = false">
    <div class="saas-modal" style="max-width:440px;" @click.stop>
      <div class="saas-modal-header"><h2 style="font-size:17px;font-weight:700;color:#111827;margin:0;">Modifier le client</h2><button @click="showEdit = false" style="width:32px;height:32px;border-radius:8px;background:rgba(0,0,0,0.05);border:none;cursor:pointer;display:grid;place-items:center;"><svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;color:#6B7280;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
      <div class="saas-modal-body">
        <div x-show="editError" style="background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.2);border-radius:10px;padding:12px 14px;margin-bottom:16px;font-size:13px;color:#DC2626;" x-text="editError"></div>
        <div style="display:grid;gap:14px;">
          <div><label class="saas-label">Nom complet *</label><input type="text" class="saas-input" placeholder="Prénom Nom" x-model="editForm.name"></div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;"><div><label class="saas-label">Téléphone</label><input type="tel" class="saas-input" placeholder="+225 07..." x-model="editForm.phone"></div><div><label class="saas-label">Email</label><input type="email" class="saas-input" placeholder="email@..." x-model="editForm.email"></div></div>
          <div><label class="saas-label">Adresse</label><input type="text" class="saas-input" placeholder="Quartier, Ville..." x-model="editForm.address"></div>
        </div>
      </div>
      <div class="saas-modal-footer"><button @click="showEdit = false" class="btn-saas-secondary">Annuler</button><button @click="saveEdit()" class="btn-saas-primary" :disabled="editSaving"><div x-show="editSaving" style="width:14px;height:14px;border-radius:50%;border:2px solid rgba(255,255,255,0.3);border-top-color:white;animation:spin 0.7s linear infinite;"></div><span x-show="!editSaving">Enregistrer</span><span x-show="editSaving">Sauvegarde...</span></button></div>
    </div>
  </div>

  <!-- Toast -->
  <div x-show="toast" style="position:fixed;bottom:24px;right:24px;z-index:200;min-width:300px;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    <div style="background:white;border-radius:12px;padding:14px 18px;box-shadow:0 8px 24px rgba(0,0,0,0.12);display:flex;align-items:center;gap:12px;" :style="toast?.type==='success' ? 'border-left:4px solid #16a34a;' : 'border-left:4px solid #DC2626;'">
      <svg x-show="toast?.type==='success'" xmlns="http://www.w3.org/2000/svg" style="width:17px;height:17px;color:#16a34a;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
      <svg x-show="toast?.type!=='success'" xmlns="http://www.w3.org/2000/svg" style="width:17px;height:17px;color:#DC2626;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      <span style="font-size:14px;font-weight:500;color:#111827;" x-text="toast?.msg"></span>
    </div>
  </div>
</div>
