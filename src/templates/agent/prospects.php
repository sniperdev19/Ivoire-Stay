<?php
$base_url = $base_url ?? rtrim(APP_URL, '/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Mes prospects – Afristay</title>
  <meta name="theme-color" content="#1B4332">
  <link rel="manifest" href="<?= $base_url ?>/manifest-agent.webmanifest">
  <link rel="icon" href="<?= $base_url ?>/assets/icons/icon-192-agent.png" type="image/png">
  <link rel="apple-touch-icon" href="<?= $base_url ?>/assets/icons/apple-touch-icon-agent.png">
  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/fonts.css">
  <link rel="stylesheet" href="<?= $base_url ?>/assets/vendor/leaflet/leaflet.css">
  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/pages/agent-dashboard.css">
  <script src="<?= $base_url ?>/assets/js/pwa.js"></script>
  <script src="<?= $base_url ?>/assets/vendor/leaflet/leaflet.js"></script>
  <script src="<?= $base_url ?>/assets/js/pages/agent-prospects.js"></script>
  <script defer src="<?= $base_url ?>/assets/js/alpine.min.js"></script>
</head>
<body x-data="agentProspectsPage('<?= $base_url ?>')" x-init="init()">

  <div class="ag-shell">

  <header class="ag-header">
    <div class="ag-brand">
      <div class="ag-brand-sq" x-text="initials"></div>
      <div class="ag-brand-text">
        <span class="ag-brand-title" x-text="agent.nom ? ('Bonjour, ' + agent.nom) : 'Espace agent'"></span>
        <span class="ag-brand-sub">Mes prospects</span>
      </div>
    </div>
    <div class="ag-header-actions">
      <button class="ag-logout" @click="logout()">
        <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>
        Déconnexion
      </button>
    </div>
  </header>

  <div class="ag-container ag-container--with-navbar">

    <div class="ag-section-head">
      <h2>Mes prospects (<span x-text="prospects.length"></span>)</h2>
    </div>

    <div class="ag-tabs">
      <button type="button" class="ag-tab" :class="view === 'list' ? 'is-active' : ''" @click="setView('list')">Liste</button>
      <button type="button" class="ag-tab" :class="view === 'map' ? 'is-active' : ''" @click="setView('map')">
        Carte <span x-show="geolocatedCount" x-text="'(' + geolocatedCount + ')'"></span>
      </button>
    </div>

    <!-- ═══ Vue : Liste ═══ -->
    <template x-if="view === 'list'">
      <div>
        <div class="ag-filter-row">
          <button type="button" class="ag-filter-chip" :class="filterStatus === 'all' ? 'is-active' : ''" @click="filterStatus = 'all'">Tous</button>
          <template x-for="s in statusList" :key="s.value">
            <button type="button" class="ag-filter-chip" :class="filterStatus === s.value ? 'is-active' : ''" @click="filterStatus = s.value" x-text="s.label"></button>
          </template>
        </div>

        <template x-if="loaded && filteredProspects.length === 0">
          <div class="ag-empty-card">
            <div class="ag-empty-illu">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
            </div>
            <div class="ag-empty-title">Aucun prospect pour le moment.</div>
            <div class="ag-empty-sub">Ajoutez un établissement démarché pour commencer à suivre votre prospection.</div>
          </div>
        </template>

        <div class="ag-prospect-list">
          <template x-for="p in filteredProspects" :key="p.id">
            <div class="ag-prospect-card">
              <div class="ag-prospect-main" @click="openEditModal(p)">
                <div class="ag-prospect-name" x-text="p.establishment_name"></div>
                <div class="ag-prospect-phone" x-text="p.phone"></div>
                <div class="ag-prospect-date">
                  Ajouté le <span x-text="formatDate(p.created_at)"></span>
                  <template x-if="p.latitude === null"><span> · non localisé</span></template>
                </div>
              </div>
              <div class="ag-prospect-side">
                <select class="ag-status-select" :class="statusMeta(p.status).badge" x-model="p.status" @change="updateStatus(p, $event.target.value)" @click.stop>
                  <template x-for="s in statusList" :key="s.value">
                    <option :value="s.value" x-text="s.label"></option>
                  </template>
                </select>
                <button type="button" class="ag-icon-danger" @click.stop="askDelete(p.id)" aria-label="Supprimer">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0l-1 14a2 2 0 01-2 2H7a2 2 0 01-2-2L4 6h16z"/></svg>
                </button>
              </div>
            </div>
          </template>
        </div>
      </div>
    </template>

    <!-- ═══ Vue : Carte ═══ -->
    <template x-if="view === 'map'">
      <div>
        <template x-if="geolocatedCount === 0">
          <div class="ag-empty-card" style="margin-bottom:14px;">
            <div class="ag-empty-title">Aucun prospect géolocalisé.</div>
            <div class="ag-empty-sub">Autorisez la localisation lors de l'ajout d'un prospect pour le voir apparaître ici.</div>
          </div>
        </template>
        <div id="agent-prospects-map" class="ag-map-wrap"></div>
        <div class="ag-map-legend">
          <template x-for="s in statusList" :key="s.value">
            <span class="ag-map-legend-item">
              <span class="ag-map-legend-dot" :style="`background:${statusMeta(s.value).color}`"></span>
              <span x-text="s.label"></span>
            </span>
          </template>
        </div>
      </div>
    </template>

  </div>

  </div>

  <!-- ═══ Navigation basse ═══ -->
  <nav class="ag-bottomnav">
    <a href="<?= $base_url ?>/agent/dashboard" class="ag-bn-item">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11.5L12 4l9 7.5"/><path d="M5 10v9a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1v-9"/></svg>
      <span>Accueil</span>
    </a>
    <a href="<?= $base_url ?>/agent/dashboard?view=establishments" class="ag-bn-item">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1m-1 4h1m4-4h1m-1 4h1M9 21v-4h6v4"/></svg>
      <span>Établissements</span>
    </a>
    <a href="<?= $base_url ?>/agent/dashboard?view=bonuses" class="ag-bn-item">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="20 12 20 22 4 22 4 12"></polyline>
        <rect x="2" y="7" width="20" height="5"></rect>
        <line x1="12" y1="22" x2="12" y2="7"></line>
        <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path>
        <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path>
      </svg>
      <span>Primes</span>
    </a>
    <div class="ag-bn-scan-wrap">
      <a href="<?= $base_url ?>/agent/dashboard?scan=1" class="ag-bn-scan" aria-label="Scanner">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3.75" y="3.75" width="6" height="6" rx="1.2"/>
          <rect x="3.75" y="14.25" width="6" height="6" rx="1.2"/>
          <rect x="14.25" y="3.75" width="6" height="6" rx="1.2"/>
          <path d="M14.25 14.25h2.25v2.25h-2.25zM19.5 14.25h1.5v1.5h-1.5zM14.25 19.5h1.5v1.5h-1.5zM17.25 17.25h1.5v1.5h-1.5zM19.5 19.5h1.5v1.5h-1.5z"/>
        </svg>
      </a>
      <span class="ag-bn-scan-label">Scanner</span>
    </div>
    <a href="<?= $base_url ?>/agent/dashboard?view=history" class="ag-bn-item">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <span>Historique</span>
    </a>
    <a href="<?= $base_url ?>/agent/prospects" class="ag-bn-item is-active">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
      </svg>
      <span>Prospects</span>
    </a>
    <a href="<?= $base_url ?>/agent/profile" class="ag-bn-item">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      <span>Profil</span>
    </a>
  </nav>

  <!-- ═══ Bouton flottant : ajouter un prospect ═══ -->
  <button type="button" class="ag-fab" @click="openAddModal()" aria-label="Ajouter un prospect">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
  </button>

  <!-- ═══ Modal : ajout / édition ═══ -->
  <div class="ag-modal-bg" x-show="showModal" x-cloak @click.self="closeModal()">
    <div class="ag-modal">
      <h3 x-text="editingId ? 'Modifier le prospect' : 'Nouveau prospect'"></h3>

      <div class="ag-form-group">
        <label class="ag-form-label">Nom de l'établissement</label>
        <input type="text" class="ag-form-input" x-model="form.establishment_name" placeholder="Ex. Résidence Le Palmier">
      </div>
      <div class="ag-form-group">
        <label class="ag-form-label">Numéro de téléphone</label>
        <input type="text" class="ag-form-input" x-model="form.phone" placeholder="01 23 45 67 89">
      </div>
      <div class="ag-form-group">
        <label class="ag-form-label">Notes (optionnel)</label>
        <input type="text" class="ag-form-input" x-model="form.notes" placeholder="Contexte, rendez-vous, remarques...">
      </div>

      <div class="ag-form-group">
        <label class="ag-form-label">Localisation</label>
        <div class="ag-geo-row">
          <template x-if="geo.state === 'ok'">
            <span class="ag-geo-status ok">Position enregistrée</span>
          </template>
          <template x-if="geo.state === 'locating'">
            <span class="ag-geo-status">Localisation en cours…</span>
          </template>
          <template x-if="geo.state === 'error'">
            <span class="ag-geo-status err">Localisation indisponible</span>
          </template>
          <template x-if="geo.state === 'idle'">
            <span class="ag-geo-status">Non localisé</span>
          </template>
          <button type="button" class="ag-btn ag-btn-ghost" @click="locate()" :disabled="geo.state === 'locating'">Localiser maintenant</button>
        </div>
      </div>

      <template x-if="saveError">
        <div class="ag-form-msg err" x-text="saveError"></div>
      </template>

      <div style="display:flex;gap:10px;margin-top:14px;">
        <button type="button" class="ag-btn ag-btn-ghost" @click="closeModal()" style="flex:1;">Annuler</button>
        <button type="button" class="ag-btn ag-btn-gold" @click="saveProspect()" :disabled="saving" style="flex:1;">
          <span x-show="!saving">Enregistrer</span>
          <span x-show="saving">Enregistrement…</span>
        </button>
      </div>
    </div>
  </div>

  <!-- ═══ Modal : confirmation suppression ═══ -->
  <div class="ag-modal-bg" x-show="confirmDeleteId !== null" x-cloak @click.self="confirmDeleteId = null">
    <div class="ag-modal">
      <h3>Supprimer ce prospect ?</h3>
      <p style="font-size:13px;color:rgba(27,67,50,0.65);margin-bottom:16px;">Cette action est définitive.</p>
      <div style="display:flex;gap:10px;">
        <button type="button" class="ag-btn ag-btn-ghost" @click="confirmDeleteId = null" style="flex:1;">Annuler</button>
        <button type="button" class="ag-btn ag-btn-danger" @click="confirmDelete()" style="flex:1;">Supprimer</button>
      </div>
    </div>
  </div>

</body>
</html>
