<?php
$base_url = $base_url ?? rtrim(APP_URL, '/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Mon profil agent – Afristay</title>
  <meta name="theme-color" content="#1B4332">
  <link rel="manifest" href="<?= $base_url ?>/manifest-agent.webmanifest">
  <link rel="icon" href="<?= $base_url ?>/assets/icons/icon-192-agent.png" type="image/png">
  <link rel="apple-touch-icon" href="<?= $base_url ?>/assets/icons/apple-touch-icon-agent.png">
  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/fonts.css">
  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/pages/agent-dashboard.css">
  <script src="<?= $base_url ?>/assets/js/pwa.js"></script>
  <script src="<?= $base_url ?>/assets/js/pages/agent-profile.js"></script>
  <script defer src="<?= $base_url ?>/assets/js/alpine.min.js"></script>
</head>
<body x-data="agentProfilePage('<?= $base_url ?>')" x-init="init()">

  <div class="ag-shell">

  <header class="ag-header">
    <div class="ag-brand">
      <div class="ag-brand-sq" x-text="initials"></div>
      <div class="ag-brand-text">
        <span class="ag-brand-title" x-text="agent.nom ? ('Bonjour, ' + agent.nom) : 'Espace agent'"></span>
        <span class="ag-brand-sub">Mon profil</span>
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

    <div class="ag-section">
      <h2>Informations</h2>
      <div class="ag-card">
        <div class="ag-form-group">
          <label class="ag-form-label">Nom complet</label>
          <input type="text" class="ag-form-input" x-model="form.nom">
        </div>
        <div class="ag-form-group">
          <label class="ag-form-label">Numéro de téléphone</label>
          <input type="text" class="ag-form-input" x-model="form.numero" placeholder="01 23 45 67 89">
          <p class="ag-form-hint">Sert à la fois pour la connexion et pour recevoir vos versements Mobile Money.</p>
        </div>
        <div class="ag-form-group">
          <label class="ag-form-label">Opérateur Mobile Money</label>
          <select class="ag-form-input" x-model="form.operateur_money">
            <option value="orange">Orange Money</option>
            <option value="mtn">MTN Mobile Money</option>
            <option value="moov">Moov Money</option>
            <option value="wave">Wave</option>
          </select>
        </div>

        <template x-if="saveError">
          <div class="ag-form-msg err" x-text="saveError"></div>
        </template>
        <template x-if="saveOk">
          <div class="ag-form-msg ok">Profil mis à jour avec succès.</div>
        </template>

        <button type="button" class="ag-btn ag-btn-gold" @click="saveProfile()" :disabled="saving" style="margin-top:6px;">
          <span x-show="!saving">Enregistrer</span>
          <span x-show="saving">Enregistrement…</span>
        </button>
      </div>
    </div>

    <div class="ag-section">
      <h2>Changer le mot de passe</h2>
      <div class="ag-card">
        <div class="ag-form-group">
          <label class="ag-form-label">Mot de passe actuel</label>
          <input type="password" class="ag-form-input" x-model="pwForm.current_password">
        </div>
        <div class="ag-form-group">
          <label class="ag-form-label">Nouveau mot de passe</label>
          <input type="password" class="ag-form-input" x-model="pwForm.new_password">
        </div>
        <div class="ag-form-group">
          <label class="ag-form-label">Confirmer le nouveau mot de passe</label>
          <input type="password" class="ag-form-input" x-model="pwForm.new_password_confirm">
        </div>

        <template x-if="pwError">
          <div class="ag-form-msg err" x-text="pwError"></div>
        </template>
        <template x-if="pwOk">
          <div class="ag-form-msg ok">Mot de passe modifié avec succès.</div>
        </template>

        <button type="button" class="ag-btn ag-btn-gold" @click="changePassword()" :disabled="pwSaving" style="margin-top:6px;">
          <span x-show="!pwSaving">Modifier le mot de passe</span>
          <span x-show="pwSaving">Modification…</span>
        </button>
      </div>
    </div>

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
    <a href="<?= $base_url ?>/agent/prospects" class="ag-bn-item">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
      </svg>
      <span>Prospects</span>
    </a>
    <a href="<?= $base_url ?>/agent/profile" class="ag-bn-item is-active">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      <span>Profil</span>
    </a>
  </nav>

</body>
</html>
