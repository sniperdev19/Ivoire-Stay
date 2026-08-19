<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php ?>

<?php $pageJs = 'saas-rooms'; $pageCss = 'saas-rooms'; ?>
<div x-data="roomsPage('<?= $base_url ?>')"
 x-init="init()"
 @click="statusMenuRoom = null; showViewMenu = false">

  <!-- En-tête et actions -->
  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:24px;">
    <div>
      <h1 style="font-size:26px;font-weight:700;margin:0;color:#111827;">Chambres & Tarifs</h1>
      <p style="margin:10px 0 0;color:#6B7280;font-size:14px;">Gestion centralisée de vos chambres et de vos types de tarifs.</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end;">
      <button type="button" class="btn-saas-primary" @click="openCreateRoom()" :disabled="establishment?.frozen_at" :style="establishment?.frozen_at ? 'opacity:0.5;cursor:not-allowed;' : ''">
        <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Ajouter une chambre
      </button>
    </div>
  </div>

  <!-- Bandeau établissement gelé (limite d'établissements du plan dépassée) -->
  <div x-show="establishment?.frozen_at" x-cloak
    style="display:flex;align-items:flex-start;gap:12px;background:#FEF2F2;border:1px solid rgba(220,38,38,0.25);border-radius:12px;padding:14px 16px;margin-bottom:20px;">
    <span style="font-size:18px;line-height:1;">🧊</span>
    <div style="font-size:13px;color:#991B1B;">
      <strong>Établissement gelé.</strong> La limite d'établissements de votre plan actuel est dépassée.
      Impossible d'ajouter une nouvelle chambre. Mettez à niveau votre abonnement pour le réactiver.
    </div>
  </div>

  <!-- Sélecteur de vue -->
  <div style="position:relative;display:inline-block;margin-bottom:24px;">
    <button type="button" class="room-view-select" @click.stop="showViewMenu = !showViewMenu">
      <span x-text="activeTab === 'rooms' ? 'Chambres' : 'Types & Tarifs'"></span>
      <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="showViewMenu" x-cloak class="status-menu" style="top:calc(100% + 6px);left:0;right:auto;min-width:200px;" @click.stop>
      <button type="button" class="status-menu-item" @click="activeTab = 'rooms'; showViewMenu = false">Chambres</button>
      <button type="button" class="status-menu-item" @click="activeTab = 'types'; showViewMenu = false">Types & Tarifs</button>
    </div>
  </div>

  <!-- Statistiques rapides -->
  <div class="room-stats">
    <div class="kpi-card">
      <div class="kpi-top"><div class="kpi-icon kpi-icon-gray"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 18v-11a2 2 0 012-2h4a2 2 0 012 2v11M4 13h16M20 18v-5a2 2 0 00-2-2h-6M4 18h16M4 21v-3M20 21v-3"/></svg></div></div>
      <div class="kpi-body"><div class="kpi-value" x-text="rooms.length"></div><div class="kpi-label">Total</div></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><div class="kpi-icon kpi-icon-blue"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div></div>
      <div class="kpi-body"><div class="kpi-value" x-text="countStatus('available')"></div><div class="kpi-label">Disponibles</div></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><div class="kpi-icon kpi-icon-green"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg></div></div>
      <div class="kpi-body"><div class="kpi-value" x-text="countStatus('occupied')"></div><div class="kpi-label">Occupées</div></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><div class="kpi-icon kpi-icon-amber"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4.5v6.586a1 1 0 00.293.707l5.414 5.414a2 2 0 002.828-2.828l-5.414-5.414A1 1 0 0014 8.172V4.5m-3 0a1.5 1.5 0 013 0m-3 0h3M5.5 19.5l3-3m0 0a1.5 1.5 0 10-2.121-2.121 1.5 1.5 0 002.121 2.121z"/></svg></div></div>
      <div class="kpi-body"><div class="kpi-value" x-text="countStatus('maintenance') + countStatus('cleaning')"></div><div class="kpi-label">Maint. / Ménage</div></div>
    </div>
  </div>

  <!-- Filters et vue -->
  <div class="saas-card" style="padding:10px;margin-bottom:16px;">
    <div class="room-info">
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <span class="filter-pill" :class="{ 'active': filterStatus === 'all' }" @click="filterStatus='all'">Toutes</span>
        <span class="filter-pill" :class="{ 'active': filterStatus === 'available' }" @click="filterStatus='available'">Disponibles</span>
        <span class="filter-pill" :class="{ 'active': filterStatus === 'occupied' }" @click="filterStatus='occupied'">Occupées</span>
        <span class="filter-pill" :class="{ 'active': filterStatus === 'maintenance' }" @click="filterStatus='maintenance'">Maintenance</span>
        <span class="filter-pill" :class="{ 'active': filterStatus === 'cleaning' }" @click="filterStatus='cleaning'">Ménage</span>
      </div>
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <select class="saas-input" style="min-width:220px;" x-model="filterType">
          <option value="">Tous les types</option>
          <template x-for="type in roomTypes" :key="type.id">
            <option :value="type.id" x-text="type.name"></option>
          </template>
        </select>
        <button type="button" class="btn-saas-secondary" @click="filterStatus='all'; filterType='';">Effacer les filtres</button>
        <div style="display:flex;gap:6px;">
          <button type="button" class="btn-saas-secondary" :class="viewMode==='grid' ? 'active' : ''" @click="viewMode='grid'" style="padding:9px 12px;">Grille</button>
          <button type="button" class="btn-saas-secondary" :class="viewMode==='list' ? 'active' : ''" @click="viewMode='list'" style="padding:9px 12px;">Liste</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Onglet Chambres -->
  <div x-show="activeTab === 'rooms'">
    <div x-show="loadingRooms" style="display:grid;gap:16px;">
      <div class="saas-card" style="height:120px;">Chargement des chambres…</div>
      <div class="saas-card" style="height:120px;">Chargement des chambres…</div>
    </div>

    <div x-show="!loadingRooms">
      <template x-if="filteredRooms.length === 0">
        <div class="saas-card" style="text-align:center;padding:40px 20px;">
          <div style="font-size:18px;font-weight:700;color:#111827;margin-bottom:8px;" x-text="rooms.length === 0 ? 'Aucune chambre configurée' : 'Aucune chambre ne correspond à vos filtres'"></div>
          <div style="color:#6B7280;font-size:14px;" x-text="rooms.length === 0 ? 'Cliquez sur « Ajouter une chambre » pour commencer.' : 'Essayez de modifier ou d\'effacer les filtres.'"></div>
          <button x-show="rooms.length > 0" type="button" class="btn-saas-primary" style="margin-top:18px;" @click="filterStatus='all'; filterType='';">Effacer les filtres</button>
        </div>
      </template>

      <template x-if="filteredRooms.length > 0">
        <div>
          <div x-show="viewMode === 'grid'" class="room-grid">
            <template x-for="room in roomsByFloor.flatMap(([floor, rooms]) => rooms)" :key="room.id">
              <div class="room-card room-card-rich" :class="'status-' + room.status" @click="openEditRoom(room)">

                <div class="card-header-row">
                  <div style="min-width:0;">
                    <div class="room-card-number-lg" x-text="room.number"></div>
                    <div class="room-card-type-badge" x-show="room.room_type?.name">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5a1 1 0 01.707.293l8 8a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-8-8A1 1 0 015 9V4a1 1 0 011-1z"/></svg>
                      <span x-text="room.room_type?.name"></span>
                    </div>
                  </div>
                  <div class="card-icon-actions">
                    <button type="button" class="card-icon-btn" title="Modifier" @click.stop="openEditRoom(room)">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button type="button" class="card-icon-btn tarifs" title="Tarifs" @click.stop="room.room_type && (openEditType(room.room_type), typeTab = 2)" :disabled="!room.room_type">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5a1 1 0 01.707.293l8 8a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-8-8A1 1 0 015 9V4a1 1 0 011-1z"/></svg>
                    </button>
                    <button type="button" class="card-icon-btn danger" title="Supprimer" @click.stop="deleteRoom(room.id)">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                  </div>
                </div>

                <div class="room-card-status-row">
                  <button type="button" class="room-card-status-trigger" @click.stop="statusMenuRoom = room.id">
                    <span class="status-dot" :style="{ background: statusCfg(room.status).dot }"></span>
                    <span x-text="statusCfg(room.status).label"></span>
                  </button>
                  <span class="room-card-floor-tag" x-show="room.floor" x-text="'Étage ' + room.floor"></span>
                </div>

                <div class="price-group" x-show="room.room_type">
                  <div class="price-group-col price-group-main">
                    <span class="price-group-label">Nuit</span>
                    <span class="price-group-value" x-text="formatPrice(room.room_type?.base_price)"></span>
                  </div>
                  <div class="price-group-col">
                    <span class="price-group-label">Week-end</span>
                    <span class="price-group-value secondary" x-text="room.room_type?.weekend_price ? formatPrice(room.room_type.weekend_price) : '—'"></span>
                  </div>
                  <div class="price-group-col">
                    <span class="price-group-label">Passage/h</span>
                    <span class="price-group-value secondary" x-text="room.room_type?.passage_price ? formatPrice(room.room_type.passage_price) : '—'"></span>
                  </div>
                </div>

                <div class="room-card-amenities" x-show="room.room_type?.amenities?.length">
                  <template x-for="a in (room.room_type?.amenities || [])" :key="a">
                    <span class="room-amenity-chip">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                      <span x-text="a"></span>
                    </span>
                  </template>
                </div>

                <div x-show="statusMenuRoom === room.id" class="status-menu" @click.stop>
                  <button type="button" class="status-menu-item" @click="quickStatus(room.id,'available')">
                    <span class="status-dot" style="background:#2563EB;"></span>
                    Disponible
                  </button>
                  <button type="button" class="status-menu-item" @click="quickStatus(room.id,'occupied')">
                    <span class="status-dot" style="background:#16A34A;"></span>
                    Occupée
                  </button>
                  <button type="button" class="status-menu-item" @click="quickStatus(room.id,'cleaning')">
                    <span class="status-dot" style="background:#7C3AED;"></span>
                    Ménage
                  </button>
                  <button type="button" class="status-menu-item" @click="quickStatus(room.id,'maintenance')">
                    <span class="status-dot" style="background:#D97706;"></span>
                    Maintenance
                  </button>
                  <button type="button" class="status-menu-item" @click="quickStatus(room.id,'blocked')">
                    <span class="status-dot" style="background:#6B7280;"></span>
                    Bloquée
                  </button>
                </div>
              </div>
            </template>
          </div>

          <div x-show="viewMode === 'list'" style="overflow-x:auto;">
            <table class="room-table">
              <thead>
                <tr>
                  <th>Chambre</th>
                  <th>Type</th>
                  <th>Étage</th>
                  <th>Prix</th>
                  <th>Statut</th>
                  <th style="width:180px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <template x-for="room in filteredRooms" :key="room.id">
                  <tr>
                    <td>
                      <div style="font-weight:700;color:#111827;">Chambre <span x-text="room.number"></span></div>
                      <div style="font-size:13px;color:#6B7280;" x-text="room.notes || 'Aucune note'"></div>
                    </td>
                    <td x-text="room.room_type?.name || '—'"></td>
                    <td x-text="room.floor || '-'" style="color:#4B5563;"></td>
                    <td style="font-weight:700;color:#111827;" x-text="formatPrice(room.room_type?.base_price)"></td>
                    <td><span :class="statusCfg(room.status).badge" :style="statusCfg(room.status).badgeStyle || ''" x-text="statusCfg(room.status).label"></span></td>
                    <td>
                      <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <button type="button" class="btn-saas-secondary" @click="openEditRoom(room)">Modifier</button>
                        <button type="button" class="btn-saas-secondary" @click.stop="statusMenuRoom = room.id">Statut</button>
                        <button type="button" class="btn-saas-danger" @click="deleteRoom(room.id)">Supprimer</button>
                      </div>
                      <div x-show="statusMenuRoom === room.id" class="status-menu" @click.stop>
                        <button type="button" class="status-menu-item" @click="quickStatus(room.id,'available')">Disponible</button>
                        <button type="button" class="status-menu-item" @click="quickStatus(room.id,'occupied')">Occupée</button>
                        <button type="button" class="status-menu-item" @click="quickStatus(room.id,'cleaning')">Ménage</button>
                        <button type="button" class="status-menu-item" @click="quickStatus(room.id,'maintenance')">Maintenance</button>
                        <button type="button" class="status-menu-item" @click="quickStatus(room.id,'blocked')">Bloquée</button>
                      </div>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </div>
  </div>

  <!-- Onglet Types & Tarifs -->
  <div x-show="activeTab === 'types'">
    <div class="room-info" style="margin-bottom:18px;">
      <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
        <div style="font-size:15px;font-weight:700;color:#111827;">Types de chambre</div>
        <div style="font-size:13px;color:#6B7280;">Liste des tarifs configurés et nombre de chambres par type. Un nouveau type se crée directement depuis le formulaire « Ajouter une chambre ».</div>
      </div>
    </div>

    <template x-if="loadingTypes">
      <div class="saas-card">Chargement des types...</div>
    </template>

    <template x-if="!loadingTypes">
      <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;" class="room-grid">
        <template x-for="type in roomTypes" :key="type.id">
          <div class="type-card">
            <div class="card-header-row">
              <div style="min-width:0;">
                <div class="type-card-name" x-text="type.name"></div>
                <div class="type-card-count">
                  <svg xmlns="http://www.w3.org/2000/svg" style="width:13px;height:13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                  <span x-text="(rooms.filter(r => r.room_type_id == type.id).length) + ' chambre(s)' "></span>
                </div>
              </div>
              <div class="card-icon-actions">
                <button type="button" class="card-icon-btn" title="Modifier" @click="openEditType(type)">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <button type="button" class="card-icon-btn danger" title="Supprimer le type" @click="deleteType(type.id)">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
              </div>
            </div>

            <div class="type-card-badges-row" x-show="type.capacity || type.beds_count">
              <span class="type-card-tag" x-text="type.capacity + ' pers.'"></span>
              <span class="type-card-tag" x-show="type.beds_count" x-text="type.beds_count + ' ' + (type.bed_type || 'lit(s)')"></span>
            </div>

            <div class="price-group">
              <div class="price-group-col price-group-main">
                <span class="price-group-label">Nuit</span>
                <span class="price-group-value" x-text="formatPrice(type.base_price)"></span>
              </div>
              <div class="price-group-col">
                <span class="price-group-label">Week-end</span>
                <span class="price-group-value secondary" x-text="type.weekend_price ? formatPrice(type.weekend_price) : '—'"></span>
              </div>
              <div class="price-group-col">
                <span class="price-group-label">Passage/h</span>
                <span class="price-group-value secondary" x-text="type.passage_price ? formatPrice(type.passage_price) : '—'"></span>
              </div>
            </div>

            <div class="type-card-desc" x-text="type.description || 'Aucune description renseignée.'"></div>

            <div class="type-card-amenities" x-show="type.amenities && type.amenities.length">
              <template x-for="a in (type.amenities || [])" :key="a">
                <span class="type-amenity-tag" x-text="a"></span>
              </template>
            </div>
          </div>
        </template>
      </div>
    </template>
  </div>

  <!-- Modal chambre -->
  <div x-cloak x-show="showRoomModal" class="saas-modal-bg" @click.self="showRoomModal=false" @keydown.escape.window="showRoomModal=false">
    <form class="saas-modal room-modal-v2" @click.stop @submit.prevent="saveRoom()">

      <div class="room-modal-v2-header">
        <div class="room-modal-v2-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        </div>
        <div class="room-modal-v2-heading">
          <h2 x-text="editingRoom ? 'Modifier la chambre' : 'Nouvelle chambre'"></h2>
          <p x-text="editingRoom ? 'Modifiez les informations de cette chambre.' : 'Ajoutez une nouvelle chambre et configurez ses tarifs.'"></p>
        </div>
        <button type="button" class="type-modal-close" @click="showRoomModal=false">✕</button>
      </div>

      <div class="room-modal-v2-scroll">

        <div class="create-steps">
          <div class="create-step" :class="{ active: roomStep === 1, done: roomStep > 1 }">
            <span class="create-step-num" x-text="roomStep > 1 ? '✓' : '1'"></span> Informations
          </div>
          <div class="create-step-line" :class="{ done: roomStep > 1 }"></div>
          <div class="create-step" :class="{ active: roomStep === 2, done: roomStep > 2 }">
            <span class="create-step-num" x-text="roomStep > 2 ? '✓' : '2'"></span> Tarifs & Équipements
          </div>
          <div class="create-step-line" :class="{ done: roomStep > 2 }"></div>
          <div class="create-step" :class="{ active: roomStep === 3 }">
            <span class="create-step-num">3</span> Notes & Photos
          </div>
        </div>

        <div x-show="roomStep === 1">
        <!-- Informations générales -->
        <div>
          <div class="room-modal-v2-section-title">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 20l4-16m2 16l4-16M6 9h14M5 15h14"/></svg>
            Informations générales
          </div>

          <div class="room-modal-v2-grid3">
            <div>
              <label class="saas-label">Nom / Numéro de chambre *</label>
              <input type="text" class="saas-input" placeholder="Ex : A1, 101, Suite 5…" x-model="roomForm.number" required />
              <p class="room-price-hint">Ex : A1, 101, Suite 5…</p>
            </div>
            <div>
              <label class="saas-label">Type de chambre <span x-show="roomTypeMode==='existing'" style="color:#DC2626;">*</span></label>
              <template x-if="roomTypeMode === 'existing'">
                <div>
                  <select class="saas-input" x-model="roomForm.room_type_id" required>
                    <option value="">Sélectionner un type</option>
                    <template x-for="type in roomTypes" :key="type.id">
                      <option :value="type.id" x-text="type.name"></option>
                    </template>
                  </select>
                  <button type="button" class="room-modal-v2-link" @click="roomTypeMode = 'new'">+ Créer un nouveau type</button>
                </div>
              </template>
              <template x-if="roomTypeMode === 'new'">
                <div>
                  <input type="text" class="saas-input" placeholder="Nom du nouveau type" x-model="newTypeForm.name" required />
                  <button type="button" class="room-modal-v2-link" @click="resetNewTypeForm()">← Choisir un type existant</button>
                </div>
              </template>
            </div>
            <div>
              <label class="saas-label">Étage</label>
              <input type="text" class="saas-input" placeholder="Ex : RDC, 1er étage…" x-model="roomForm.floor" />
            </div>
          </div>

          <div class="room-modal-v2-grid3" style="margin-top:16px;">
            <div>
              <label class="saas-label">Capacité (personnes) <span x-show="roomTypeMode==='new'" style="color:#DC2626;">*</span></label>
              <select class="saas-input" x-show="roomTypeMode==='existing'" disabled>
                <option x-text="selectedRoomType ? (selectedRoomType.capacity + ' personne' + (selectedRoomType.capacity > 1 ? 's' : '')) : '—'"></option>
              </select>
              <select class="saas-input" x-show="roomTypeMode==='new'" x-model.number="newTypeForm.capacity">
                <template x-for="n in [1,2,3,4,5,6]" :key="n">
                  <option :value="n" x-text="n + ' personne' + (n > 1 ? 's' : '')"></option>
                </template>
              </select>
            </div>
            <div>
              <label class="saas-label">Type de lit</label>
              <select class="saas-input" x-show="roomTypeMode==='existing'" disabled>
                <option x-text="selectedRoomType?.bed_type || '—'"></option>
              </select>
              <select class="saas-input" x-show="roomTypeMode==='new'" x-model="newTypeForm.bed_type">
                <option value="">Sélectionner…</option>
                <template x-for="opt in typeBedOptions" :key="opt.value">
                  <option :value="opt.value" x-text="opt.label"></option>
                </template>
              </select>
            </div>
            <div>
              <label class="saas-label">Statut</label>
              <div style="position:relative;">
                <button type="button" class="room-view-select" style="width:100%;justify-content:space-between;" @click.stop="roomFormStatusMenu = !roomFormStatusMenu">
                  <span style="display:flex;align-items:center;gap:8px;">
                    <span class="status-dot" :style="{ background: statusCfg(roomForm.status).dot }"></span>
                    <span x-text="statusCfg(roomForm.status).label"></span>
                  </span>
                  <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="roomFormStatusMenu" x-cloak class="status-menu" style="top:calc(100% + 6px);left:0;right:0;" @click.stop>
                  <template x-for="opt in roomStatusOptions" :key="opt.value">
                    <button type="button" class="status-menu-item" @click="roomForm.status = opt.value; roomFormStatusMenu = false">
                      <span class="status-dot" :style="{ background: opt.color }"></span>
                      <span x-text="opt.label"></span>
                    </button>
                  </template>
                </div>
              </div>
            </div>
          </div>
        </div>
        </div>

        <div x-show="roomStep === 2">
        <!-- Tarifs -->
        <div>
          <div class="room-modal-v2-section-title">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5a1 1 0 01.707.293l8 8a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-8-8A1 1 0 015 9V4a1 1 0 011-1z"/></svg>
            Tarifs
            <span x-show="roomTypeMode==='existing' && !selectedRoomType" style="font-weight:400;font-size:12px;color:#9CA3AF;">Sélectionnez un type pour voir ses tarifs</span>
          </div>

          <div class="room-modal-v2-grid3">
            <div class="room-price-box room-price-box-base">
              <label class="saas-label">Tarif de base / nuit <span x-show="roomTypeMode==='new'" style="color:#DC2626;">*</span></label>
              <div class="room-price-value-row">
                <input type="number" min="0" class="room-price-input" x-show="roomTypeMode==='new'" x-model.number="newTypeForm.base_price" placeholder="0" />
                <span class="room-price-input" x-show="roomTypeMode==='existing'" x-text="selectedRoomType ? formatNumber(selectedRoomType.base_price) : '—'"></span>
                <span class="room-price-suffix">FCFA</span>
              </div>
              <p class="room-price-hint">Tarif standard par nuit</p>
            </div>
            <div class="room-price-box room-price-box-weekend">
              <label class="saas-label">Tarif week-end / nuit</label>
              <div class="room-price-value-row">
                <input type="number" min="0" class="room-price-input" x-show="roomTypeMode==='new'" x-model.number="newTypeForm.weekend_price" placeholder="0" />
                <span class="room-price-input" x-show="roomTypeMode==='existing'" x-text="selectedRoomType?.weekend_price ? formatNumber(selectedRoomType.weekend_price) : '—'"></span>
                <span class="room-price-suffix">FCFA</span>
              </div>
              <p class="room-price-hint">Vendredi et samedi</p>
            </div>
            <div class="room-price-box room-price-box-passage">
              <label class="saas-label">Tarif passage / heure</label>
              <div class="room-price-value-row">
                <input type="number" min="0" class="room-price-input" x-show="roomTypeMode==='new'" x-model.number="newTypeForm.passage_price" placeholder="0" />
                <span class="room-price-input" x-show="roomTypeMode==='existing'" x-text="selectedRoomType?.passage_price ? formatNumber(selectedRoomType.passage_price) : '—'"></span>
                <span class="room-price-suffix">FCFA</span>
              </div>
              <p class="room-price-hint">Tarif journalier / horaire</p>
            </div>
          </div>
        </div>

        <!-- Équipements & Services -->
        <div>
          <div class="room-modal-v2-section-title">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h1A1.5 1.5 0 0113 9.5v0a1.5 1.5 0 001.5 1.5h.5a2 2 0 012 2v0a2 2 0 002 2h1.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Équipements & Services
          </div>
          <div class="amenity-grid-v2">
            <template x-for="a in typeAmenityOptions" :key="a">
              <label class="room-amenity-check" :class="{ active: (roomTypeMode==='existing' ? (selectedRoomType?.amenities||[]) : newTypeForm.amenities).includes(a) }">
                <input type="checkbox" :checked="(roomTypeMode==='existing' ? (selectedRoomType?.amenities||[]) : newTypeForm.amenities).includes(a)"
                       :disabled="roomTypeMode==='existing'" @change="roomTypeMode==='new' && toggleNewTypeAmenity(a)" />
                <svg x-show="a==='Climatisation'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 2v20M4.9 6.5l14.2 11M4.9 17.5l14.2-11"/></svg>
                <svg x-show="a==='Ventilateur'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.59 4.59A2 2 0 1111 8H2m10.59 11.41A2 2 0 1014 16H2m15.73-8.27A2.5 2.5 0 1119.5 12H2"/></svg>
                <svg x-show="a==='Smart TV'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="5" width="16" height="10" rx="1.5"/><path stroke-linecap="round" d="M9 19h6M12 15v4"/></svg>
                <svg x-show="a==='DSTV / Canal+'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 10a8 8 0 0116 0"/><circle cx="12" cy="17" r="1.6"/><path stroke-linecap="round" d="M12 15.4V10"/></svg>
                <svg x-show="a==='Réfrigérateur'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="2.5" width="12" height="19" rx="1.5"/><path stroke-linecap="round" d="M6 10h12"/></svg>
                <svg x-show="a==='Wi-Fi'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01M5.111 12.904a10.5 10.5 0 0113.778 0M1.394 9.393a15.5 15.5 0 0121.212 0"/></svg>
                <svg x-show="a==='Bain / Douche'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="12" width="16" height="7" rx="3"/><path stroke-linecap="round" d="M7 12V7a2 2 0 012-2"/></svg>
                <svg x-show="a==='Eau chaude'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="14" r="6"/><path stroke-linecap="round" d="M9 5.5s1 1.5 0 3M13 4.5s1 1.5 0 3"/></svg>
                <svg x-show="a==='Coffre-fort'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" d="M12 9V6.5"/></svg>
                <svg x-show="a==='Mini bar'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 4h12l-1.5 6a4.5 4.5 0 01-9 0L6 4z"/><path stroke-linecap="round" d="M12 14v6M9 20h6"/></svg>
                <svg x-show="a==='Balcon'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="10"/><path stroke-linecap="round" d="M4 10V6M20 10V6M12 10V6"/></svg>
                <svg x-show="a==='Moustiquaire'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 9a8 8 0 0116 0M5 9v10M19 9v10M9 9v10M15 9v10"/></svg>
                <svg x-show="a==='Groupe électrogène'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                <svg x-show="a==='Petit-déjeuner inclus'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z"/><path stroke-linecap="round" d="M6 2v3M10 2v3M14 2v3"/></svg>
                <svg x-show="a==='Parking privé'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M10 16V8h3a2.5 2.5 0 010 5h-3"/></svg>
                <svg x-show="a==='Sèche-cheveux'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a5 5 0 015-5c3 0 6 1.5 8 5-2 1.5-3 2-4 2H8a5 5 0 01-5-2z"/><path stroke-linecap="round" d="M7 11v8M11 11v6"/><path stroke-linecap="round" d="M18 6c1.2.9 1.2 2.1 0 3M20.5 5c1.8 1.5 1.8 3.5 0 5"/></svg>
                <svg x-show="a==='Bureau de travail'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2 8h20M4 8v12M20 8v12"/><rect x="8" y="8" width="8" height="5" rx="1"/></svg>
                <svg x-show="a==='Prises USB'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 2v6M15 2v6M6 8h12v4a6 6 0 01-12 0V8z"/><path stroke-linecap="round" d="M12 18v4"/></svg>
                <span x-text="a"></span>
              </label>
            </template>
          </div>
        </div>
        </div>

        <div x-show="roomStep === 3">
        <!-- Notes -->
        <div>
          <div class="room-modal-v2-section-title">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Notes
          </div>
          <textarea class="saas-input" rows="3" maxlength="250" placeholder="Ajouter une description ou des informations supplémentaires…" x-model="roomForm.notes"></textarea>
          <div style="text-align:right;font-size:11px;color:#9CA3AF;margin-top:4px;" x-text="(roomForm.notes||'').length + '/250'"></div>
        </div>

        <!-- Photos -->
        <div>
          <div class="room-modal-v2-section-title">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Photos de la chambre <span style="font-weight:400;color:#9CA3AF;">(3 maximum)</span>
          </div>
          <div style="display:flex;gap:14px;flex-wrap:wrap;">
            <template x-for="photo in roomPhotos" :key="photo.id">
              <div style="width:100px;height:100px;border-radius:12px;position:relative;overflow:hidden;flex-shrink:0;">
                <img :src="photoUrl(photo.file_path)" alt="Photo de la chambre" style="width:100%;height:100%;object-fit:cover;display:block;">
                <span x-show="photo.is_cover" style="position:absolute;bottom:5px;left:5px;background:rgba(0,0,0,0.55);color:white;font-size:9px;font-weight:600;padding:2px 6px;border-radius:5px;">Couverture</span>
                <button type="button" @click="removeRoomPhoto(photo.id)" :disabled="roomPhotoDeleting === photo.id"
                        style="position:absolute;top:5px;right:5px;width:20px;height:20px;border-radius:50%;border:none;background:rgba(0,0,0,0.55);color:white;cursor:pointer;font-size:11px;line-height:1;display:flex;align-items:center;justify-content:center;">✕</button>
              </div>
            </template>
            <template x-for="(pending, idx) in pendingRoomPhotos" :key="idx">
              <div style="width:100px;height:100px;border-radius:12px;position:relative;overflow:hidden;flex-shrink:0;">
                <img :src="pending.previewUrl" alt="Photo de la chambre" style="width:100%;height:100%;object-fit:cover;display:block;">
                <span x-show="idx === 0 && !roomPhotos.length" style="position:absolute;bottom:5px;left:5px;background:rgba(0,0,0,0.55);color:white;font-size:9px;font-weight:600;padding:2px 6px;border-radius:5px;">Couverture</span>
                <button type="button" @click="removePendingPhoto(idx)"
                        style="position:absolute;top:5px;right:5px;width:20px;height:20px;border-radius:50%;border:none;background:rgba(0,0,0,0.55);color:white;cursor:pointer;font-size:11px;line-height:1;display:flex;align-items:center;justify-content:center;">✕</button>
              </div>
            </template>
            <template x-if="(roomPhotos.length + pendingRoomPhotos.length) < 3">
              <label style="width:100px;height:100px;border-radius:12px;border:2px dashed rgba(37,99,235,0.3);background:rgba(37,99,235,0.04);display:flex;align-items:center;justify-content:center;color:#2563EB;cursor:pointer;flex-shrink:0;">
                <input type="file" accept="image/jpeg,image/png,image/webp" style="display:none;" @change="addRoomPhoto($event)" :disabled="roomPhotoUploading">
                <div x-show="roomPhotoUploading" class="stg-btn-spinner" style="border-color:rgba(37,99,235,0.3);border-top-color:#2563EB;"></div>
                <template x-if="!roomPhotoUploading">
                  <div style="text-align:center;">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    <div style="font-size:11px;margin-top:5px;">Ajouter</div>
                  </div>
                </template>
              </label>
            </template>
          </div>
          <p x-show="!editingRoom && pendingRoomPhotos.length" style="margin:8px 2px 0;font-size:12px;color:#6B7280;">Ces photos seront envoyées après la création de la chambre.</p>
          <p x-show="roomPhotoError" x-text="roomPhotoError" style="margin:8px 2px 0;font-size:12px;color:#DC2626;"></p>
        </div>
        </div>

        <div x-show="roomError" x-cloak class="room-modal-v2-error">
          <strong>Erreur :</strong> <span x-text="roomError"></span>
        </div>
      </div>

      <div class="type-modal-footer">
        <button type="button" class="btn-saas-secondary" @click="roomStep > 1 ? roomStep-- : (showRoomModal=false)">
          <span x-text="roomStep > 1 ? '← Précédent' : 'Annuler'"></span>
        </button>
        <button type="button" class="btn-saas-primary" x-show="roomStep < 3"
          :disabled="(roomStep === 1 && !canProceedRoomStep1) || (roomStep === 2 && !canProceedRoomStep2)"
          @click="roomStep++">Continuer →</button>
        <button type="submit" class="btn-saas-primary" x-show="roomStep === 3" :disabled="roomSaving">
          <svg xmlns="http://www.w3.org/2000/svg" style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          <span x-show="!roomSaving" x-text="editingRoom ? 'Enregistrer' : 'Enregistrer la chambre'"></span>
          <span x-show="roomSaving">Sauvegarde...</span>
        </button>
      </div>
    </form>
  </div>

  <!-- Modal type de chambre -->
  <div x-cloak x-show="showTypeModal" class="saas-modal-bg" @click.self="showTypeModal=false" @keydown.escape.window="showTypeModal=false">
    <form class="saas-modal type-modal" @click.stop @submit.prevent="saveType()">
      <div class="type-modal-body">
        <!-- Colonne gauche : formulaire -->
        <div class="type-modal-form">
         <div class="type-modal-scroll">
          <div class="type-modal-title">
            <div class="type-modal-eyebrow">Configuration d'hébergement</div>
            <div class="type-modal-heading">
              <svg xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;color:var(--saas-gold);" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              <h2 x-text="editingType ? 'Modifier le Type de Chambre' : 'Nouveau Type de Chambre'"></h2>
              <button type="button" class="type-modal-close" @click="showTypeModal=false">✕</button>
            </div>
          </div>

          <div class="type-tabs">
            <button type="button" class="type-tab" :class="{ active: typeTab === 1 }" @click="typeTab = 1">1. Détails & Capacités</button>
            <button type="button" class="type-tab" :class="{ active: typeTab === 2 }" :disabled="!canProceedTypeTab1" @click="canProceedTypeTab1 && (typeTab = 2)">2. Tarifs & Calendrier</button>
            <button type="button" class="type-tab" :class="{ active: typeTab === 3 }" :disabled="!canProceedTypeTab1 || !canProceedTypeTab2" @click="(canProceedTypeTab1 && canProceedTypeTab2) && (typeTab = 3)">3. Équipements</button>
          </div>

          <div class="type-tab-panels">
          <!-- Onglet 1 : Détails & Capacités -->
          <div x-show="typeTab === 1" style="display:grid;gap:18px;">
            <div>
              <label class="saas-label">Nom du type de chambre *</label>
              <input type="text" class="saas-input" placeholder="ex: Chambre Standard, Suite Premium Lagune…" x-model="typeForm.name" required />
            </div>

            <div>
              <label class="saas-label">Capacité (personnes) *</label>
              <div class="stepper">
                <button type="button" class="stepper-btn" @click="typeForm.capacity = Math.max(1, (Number(typeForm.capacity)||1) - 1)">−</button>
                <span class="stepper-value" x-text="typeForm.capacity"></span>
                <button type="button" class="stepper-btn" @click="typeForm.capacity = (Number(typeForm.capacity)||1) + 1">+</button>
              </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
              <div>
                <label class="saas-label">Type de literie</label>
                <div class="bed-options">
                  <template x-for="opt in typeBedOptions" :key="opt.value">
                    <button type="button" class="bed-option" :class="{ active: typeForm.bed_type === opt.value }" @click="typeForm.bed_type = opt.value" x-text="opt.label"></button>
                  </template>
                </div>
              </div>
              <div>
                <label class="saas-label">Nombre de lits</label>
                <div class="beds-count-box">
                  <span style="font-size:12px;color:#6B7280;">Quantité installée :</span>
                  <div class="stepper">
                    <button type="button" class="stepper-btn" @click="typeForm.beds_count = Math.max(1, (Number(typeForm.beds_count)||1) - 1)">−</button>
                    <span class="stepper-value" x-text="typeForm.beds_count"></span>
                    <button type="button" class="stepper-btn" @click="typeForm.beds_count = (Number(typeForm.beds_count)||1) + 1">+</button>
                  </div>
                </div>
              </div>
            </div>

            <div>
              <label class="saas-label">Description du type (en quelques mots)</label>
              <textarea class="saas-input" rows="4" placeholder="Décrivez l'espace, l'accès ou les détails qui séduiront le client…" x-model="typeForm.description"></textarea>
            </div>
          </div>

          <!-- Onglet 2 : Tarifs & Calendrier -->
          <div x-show="typeTab === 2" style="display:grid;gap:18px;">
            <div class="type-tip">
              <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;flex-shrink:0;color:var(--saas-gold);" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m0 16v1m8.485-8.485h-1M4.515 12h-1m14.142 5.657l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M12 8a4 4 0 00-4 4c0 1.5.8 2.8 2 3.5V17h4v-1.5c1.2-.7 2-2 2-3.5a4 4 0 00-4-4z"/></svg>
              <div>
                <strong>Conseil de tarification locale</strong>
                <div style="color:#6B7280;font-size:13px;margin-top:2px;">Configurez le prix de base pour les jours ouvrables, le prix majoré week-end pour maximiser votre rendement, et le prix passage pour les courts séjours à l'heure.</div>
              </div>
            </div>

            <div>
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                <label class="saas-label" style="margin:0;">Tarif de base par nuit *</label>
                <span class="type-price-pill" x-text="formatPrice(typeForm.base_price || 0)"></span>
              </div>
              <input type="number" min="0" class="saas-input" x-model.number="typeForm.base_price" required style="margin-bottom:10px;" />
              <input type="range" min="0" max="200000" step="1000" class="type-range" x-model.number="typeForm.base_price" />
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
              <div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                  <label class="saas-label" style="margin:0;">Prix week-end <span style="font-weight:400;color:#6B7280;">(optionnel)</span></label>
                  <span class="type-price-pill" x-text="formatPrice(typeForm.weekend_price || 0)"></span>
                </div>
                <input type="number" min="0" class="saas-input" x-model.number="typeForm.weekend_price" />
                <div style="font-size:12px;color:#6B7280;margin-top:6px;">S'applique automatiquement les Vendredis, Samedis et Dimanches.</div>
              </div>
              <div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                  <label class="saas-label" style="margin:0;">Tarif passage / heure <span style="font-weight:400;color:#6B7280;">(optionnel)</span></label>
                  <span class="type-price-pill" x-text="formatPrice(typeForm.passage_price || 0)"></span>
                </div>
                <input type="number" min="0" class="saas-input" x-model.number="typeForm.passage_price" placeholder="ex : 5000" />
                <div style="font-size:12px;color:#6B7280;margin-top:6px;">Tarif de transit horaire pour les réservations en journée.</div>
              </div>
            </div>
          </div>

          <!-- Onglet 3 : Équipements -->
          <div x-show="typeTab === 3" style="display:grid;gap:18px;">
            <div>
              <label class="saas-label">Sélectionnez les équipements de la chambre</label>
              <div class="amenity-grid">
                <template x-for="a in typeAmenityOptions" :key="a">
                  <button type="button" class="amenity-option" :class="{ active: typeForm.amenities.includes(a) }" @click="toggleAmenity(a)" x-text="a"></button>
                </template>
              </div>
            </div>
          </div>
          </div>

          <div x-show="typeError" x-cloak style="margin-top:6px;padding:14px;border-radius:14px;background:rgba(254,226,226,0.4);border:1px solid rgba(220,38,38,0.2);color:#991B1B;">
            <strong>Erreur :</strong> <span x-text="typeError"></span>
          </div>
         </div>

          <div class="type-modal-footer">
            <button type="button" class="btn-saas-secondary" @click="typeTab === 1 ? (showTypeModal=false) : (typeTab = typeTab - 1)" x-text="typeTab === 1 ? 'Annuler' : '← Précédent'"></button>
            <button type="button" class="btn-saas-primary" x-show="typeTab < 3"
              :disabled="(typeTab === 1 && !canProceedTypeTab1) || (typeTab === 2 && !canProceedTypeTab2)"
              @click="typeTab = typeTab + 1">Continuer →</button>
            <button type="submit" class="btn-saas-primary" x-show="typeTab === 3" :disabled="typeSaving">
              <span x-show="!typeSaving" x-text="editingType ? 'Enregistrer' : 'Créer le type'"></span>
              <span x-show="typeSaving">Sauvegarde...</span>
            </button>
          </div>
        </div>
      </div>
    </form>
  </div>

  <!-- Toast -->
  <div class="saas-toast" x-show="toast" style="display:grid;">
    <div class="toast-box" :class="toast?.type === 'error' ? 'error' : ''">
      <div style="font-size:14px;font-weight:600;" x-text="toast?.msg"></div>
    </div>
  </div>
</div>
