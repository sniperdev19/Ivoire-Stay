<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php ?>

<!--
  Template clients SaaS
  Injecté dans saas/layout.php via $content
  Variables PHP disponibles : $title, $page, $base_url
-->

<?php $pageJs = 'saas-clients'; $pageCss = 'saas-clients'; ?>
<div x-data="clientsPage('<?= $base_url ?>')"
 x-init="init()">

  <!-- En-tête et stats -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
      <h1 style="font-size:20px;font-weight:700;color:#111827;margin:0 0 4px;">Clients</h1>
      <p style="font-size:13px;color:#9CA3AF;margin:0;"><span x-text="clients.length"></span> client(s) enregistré(s)</p>
    </div>
  </div>

  <!-- KPI compactes -->
  <div class="client-kpi-grid">
    <div class="saas-card" style="padding:14px 16px;border-top:3px solid #C9A84C;">
      <div style="font-size:11px;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px;">Total clients</div>
      <div style="font-size:24px;font-weight:800;color:#111827;" x-text="clients.length"></div>
    </div>
    <div class="saas-card" style="padding:14px 16px;border-top:3px solid #16a34a;">
      <div style="font-size:11px;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px;">Avec réservations</div>
      <div style="font-size:24px;font-weight:800;color:#16a34a;" x-text="clients.filter(c => (c.total_bookings ?? 0) > 0).length"></div>
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
  <div x-cloak x-show="showDetail" class="saas-modal-bg" @keydown.escape.window="showDetail = false" @click.self="showDetail = false">
    <div class="saas-modal" style="max-width:620px;" @click.stop>
      <div class="saas-modal-header" style="flex-wrap:wrap;row-gap:12px;">
        <div style="display:flex;align-items:center;gap:12px;min-width:0;">
          <div class="client-avatar" style="width:44px;height:44px;border-radius:12px;font-size:16px;flex-shrink:0;" :style="{ background: avatarBg(selectedClient?.id) }" x-text="initials(selectedClient?.name ?? '')"></div>
          <div style="min-width:0;">
            <h2 style="font-size:17px;font-weight:700;color:#111827;margin:0 0 2px;overflow-wrap:anywhere;" x-text="selectedClient?.name ?? 'Client'"></h2>
            <div style="font-size:12px;color:#9CA3AF;">Client depuis <span x-text="formatDate(selectedClient?.created_at)"></span></div>
          </div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
          <a :href="bookingUrl(selectedClient)" class="btn-saas-primary" style="font-size:13px;padding:7px 14px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;"><svg xmlns="http://www.w3.org/2000/svg" style="width:13px;height:13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg> Réserver</a>
          <button @click="openEdit(selectedClient)" class="btn-saas-secondary" style="font-size:13px;padding:7px 14px;white-space:nowrap;"><svg xmlns="http://www.w3.org/2000/svg" style="width:13px;height:13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg> Modifier</button>
          <button type="button" class="saas-modal-close" @click="showDetail = false"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
      </div>

      <div class="saas-modal-body" style="padding:0 24px 24px;">
        <div x-show="detailLoading" style="text-align:center;padding:32px;"><div style="width:28px;height:28px;border-radius:50%;border:3px solid rgba(201,168,76,0.2);border-top-color:#C9A84C;animation:spin 0.8s linear infinite;margin:0 auto;"></div></div>

        <div x-show="!detailLoading && selectedClient">
          <!-- 3 stats -->
          <div class="client-stat-grid">
            <div class="client-stat"><div class="client-stat-value" x-text="selectedClient?.total_bookings ?? clientHistory.length"></div><div class="client-stat-label">Séjours</div></div>
            <div class="client-stat"><div class="client-stat-value" style="font-size:15px;" x-text="formatPrice(selectedClient?.total_spent ?? clientHistory.reduce((s,b)=> s + (b.total_price ?? 0),0))"></div><div class="client-stat-label">Total dépensé</div></div>
            <div class="client-stat"><div class="client-stat-value" style="font-size:13px;" x-text="formatDate(selectedClient?.last_visit ?? selectedClient?.updated_at)"></div><div class="client-stat-label">Dernière visite</div></div>
          </div>

          <!-- Contact -->
          <div class="client-contact-box">
            <div class="client-contact-title">Informations de contact</div>
            <div class="client-contact-grid">
              <div class="client-contact-item">
                <div class="client-contact-icon" style="background:rgba(22,163,74,0.08);"><svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;color:#16a34a;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg></div>
                <div class="client-contact-text"><div class="client-contact-label">Téléphone</div><div class="client-contact-value" x-text="selectedClient?.phone ?? '-'"></div></div>
              </div>
              <div class="client-contact-item">
                <div class="client-contact-icon" style="background:rgba(37,99,235,0.08);"><svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;color:#2563EB;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></div>
                <div class="client-contact-text"><div class="client-contact-label">Email</div><div class="client-contact-value" x-text="selectedClient?.email ?? '-'"></div></div>
              </div>
              <div class="client-contact-item client-contact-item--full">
                <div class="client-contact-icon" style="background:rgba(201,168,76,0.08);"><svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;color:#C9A84C;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>
                <div class="client-contact-text"><div class="client-contact-label">Adresse</div><div class="client-contact-value" x-text="selectedClient?.address ?? '-'"></div></div>
              </div>
            </div>
          </div>

          <!-- Historique -->
          <div>
            <div style="font-size:13px;font-weight:600;color:#111827;margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;"><span>Historique des séjours</span><span style="font-size:12px;font-weight:400;color:#9CA3AF;" x-text="clientHistory.length + ' séjour(s)'"></span></div>

            <div x-show="clientHistory.length === 0" style="text-align:center;padding:24px;background:#FAFAFA;border-radius:12px;font-size:13px;color:#9CA3AF;">Aucun séjour enregistré</div>

            <div x-show="clientHistory.length > 0" style="display:flex;flex-direction:column;gap:8px;">
              <template x-for="b in clientHistory" :key="b.id">
                <div class="modal-list-item">
                  <div style="display:flex;align-items:center;gap:10px;flex:1;"><div style="width:32px;height:32px;border-radius:8px;background:rgba(201,168,76,0.1);display:grid;place-items:center;flex-shrink:0;"><svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;color:#C9A84C;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg></div><div><p class="modal-value" x-text="b.room_name ?? '-'"></p><p class="modal-meta" style="margin-top:2px;"><span x-text="formatDate(b.check_in)"></span> → <span x-text="formatDate(b.check_out)"></span> · <span x-text="(b.nights ?? 1) + ' nuit(s)'"></span></p></div></div>
                  <div style="display:flex;align-items:center;gap:10px;"><span class="modal-value" x-text="formatPrice(b.total_price ?? b.amount)"></span><span :class="statusCfg(b.status).badge" x-text="statusCfg(b.status).label"></span></div>
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
  <div x-cloak x-show="showEdit" class="saas-modal-bg" @keydown.escape.window="showEdit = false" @click.self="showEdit = false">
    <div class="saas-modal" style="max-width:440px;" @click.stop>
      <div class="saas-modal-header"><h2 style="font-size:17px;font-weight:700;color:#111827;margin:0;">Modifier le client</h2><button type="button" class="saas-modal-close" @click="showEdit = false"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
      <div class="saas-modal-body">
        <div x-show="editError" style="background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.2);border-radius:10px;padding:12px 14px;margin-bottom:16px;font-size:13px;color:#DC2626;" x-text="editError"></div>
        <div style="display:grid;gap:14px;">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div><label class="saas-label">Prénom *</label><input type="text" class="saas-input" placeholder="Prénom" x-model="editForm.first_name"></div>
            <div><label class="saas-label">Nom</label><input type="text" class="saas-input" placeholder="Nom de famille" x-model="editForm.last_name"></div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;"><div><label class="saas-label">Téléphone</label><input type="tel" class="saas-input" placeholder="+225 07..." x-model="editForm.phone"></div><div><label class="saas-label">Email</label><input type="email" class="saas-input" placeholder="email@..." x-model="editForm.email"></div></div>
        </div>
      </div>
      <div class="saas-modal-footer"><button @click="showEdit = false" class="btn-saas-secondary">Annuler</button><button @click="saveEdit()" class="btn-saas-primary" :disabled="editSaving"><div x-show="editSaving" style="width:14px;height:14px;border-radius:50%;border:2px solid rgba(255,255,255,0.3);border-top-color:white;animation:spin 0.7s linear infinite;"></div><span x-show="!editSaving">Enregistrer</span><span x-show="editSaving">Sauvegarde...</span></button></div>
    </div>
  </div>

  <!-- Toast -->
  <div x-show="toast" style="position:fixed;bottom:24px;right:24px;z-index:200;min-width:300px;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    <div style="background:white;border-radius:12px;padding:14px 18px;box-shadow:0 8px 24px rgba(0,0,0,0.12);display:flex;align-items:center;gap:12px;" :style="{ 'border-left': toast?.type === 'success' ? '4px solid #16a34a' : '4px solid #DC2626' }">
      <svg x-show="toast?.type==='success'" xmlns="http://www.w3.org/2000/svg" style="width:17px;height:17px;color:#16a34a;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
      <svg x-show="toast?.type!=='success'" xmlns="http://www.w3.org/2000/svg" style="width:17px;height:17px;color:#DC2626;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      <span style="font-size:14px;font-weight:500;color:#111827;" x-text="toast?.msg"></span>
    </div>
  </div>
</div>
