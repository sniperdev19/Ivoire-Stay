<?php
$base_url = $base_url ?? rtrim(APP_URL, '/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Tableau de bord agent – Afristay</title>
  <meta name="theme-color" content="#1B4332">
  <link rel="manifest" href="<?= $base_url ?>/manifest-agent.webmanifest">
  <link rel="icon" href="<?= $base_url ?>/assets/icons/icon-192-agent.png" type="image/png">
  <link rel="apple-touch-icon" href="<?= $base_url ?>/assets/icons/apple-touch-icon-agent.png">
  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/fonts.css">
  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/pages/agent-dashboard.css">
  <script src="<?= $base_url ?>/assets/js/pwa.js"></script>
  <script src="<?= $base_url ?>/assets/vendor/jsqr/jsQR.js"></script>
  <script src="<?= $base_url ?>/assets/js/pages/agent-dashboard.js"></script>
  <script defer src="<?= $base_url ?>/assets/js/alpine.min.js"></script>
</head>
<body x-data="agentDashboardPage('<?= $base_url ?>')" x-init="init()">

  <header class="ag-header">
    <div class="ag-brand">
      <div class="ag-brand-sq">AS</div>
      <span x-text="agent.nom ? ('Bonjour, ' + agent.nom) : 'Espace agent'"></span>
    </div>
    <button class="ag-logout" @click="logout()">Déconnexion</button>
  </header>

  <nav class="ag-tabs">
    <a href="<?= $base_url ?>/agent/dashboard" class="ag-tab active">Tableau de bord</a>
    <a href="<?= $base_url ?>/agent/profile" class="ag-tab">Mon profil</a>
  </nav>

  <div class="ag-container">

    <button class="ag-scan-btn" @click="openScanner()">
      <svg xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h4v4H4V4zm0 12h4v4H4v-4zm12-12h4v4h-4V4zm0 5h4v11h-4V9zM9 4h1v6H9V4zM4 11h6v1H4v-1zm5 3h1v6H9v-6zm-5 2h1v1H4v-1z"/>
      </svg>
      Scanner le QR code d'un établissement
    </button>

    <div class="ag-progress-grid">
      <template x-for="plan in ['pro','business']" :key="plan">
        <div class="ag-progress-card">
          <h3 x-text="plan === 'pro' ? 'Plan Pro' : 'Plan Business'"></h3>
          <div class="ag-progress-bar-track">
            <div class="ag-progress-bar-fill" :style="`width:${Math.min(100, (progress[plan]?.count || 0) / (progress[plan]?.target || 1) * 100)}%`"></div>
          </div>
          <div class="ag-progress-meta">
            <span x-text="`${progress[plan]?.count || 0} / ${progress[plan]?.target || 0} abonnements`"></span>
          </div>
          <div class="ag-progress-reward" x-text="`Versement : ${formatFcfa(progress[plan]?.reward)} tous les ${progress[plan]?.target || 0}`"></div>
        </div>
      </template>
    </div>

    <div class="ag-section">
      <h2>Établissements rattachés (<span x-text="establishments.length"></span>)</h2>
      <div class="ag-list">
        <template x-if="establishments.length === 0">
          <div class="ag-list-empty">Aucun établissement rattaché pour l'instant — scannez un QR code pour commencer.</div>
        </template>
        <template x-for="e in establishments" :key="e.id">
          <div class="ag-list-row">
            <span x-text="e.establishment_name"></span>
            <span x-text="e.plan"></span>
          </div>
        </template>
      </div>
    </div>

    <div class="ag-section">
      <h2>Versements</h2>
      <div class="ag-list">
        <template x-if="payouts.length === 0">
          <div class="ag-list-empty">Aucun versement pour l'instant.</div>
        </template>
        <template x-for="p in payouts" :key="p.id">
          <div class="ag-list-row">
            <span x-text="(p.plan === 'pro' ? 'Pro' : 'Business') + ' · ' + formatFcfa(p.amount)"></span>
            <span class="ag-badge" :class="'ag-badge-' + p.status" x-text="p.status === 'pending' ? 'En attente' : (p.status === 'paid' ? 'Payé' : 'Rejeté')"></span>
          </div>
        </template>
      </div>
    </div>

  </div>

  <!-- ═══ Modal scan QR ═══ -->
  <div class="ag-modal-bg" x-show="showScanner" x-cloak @click.self="closeScanner()">
    <div class="ag-modal">
      <h3>Scanner le QR code</h3>

      <div class="ag-video-wrap" x-show="!cameraError">
        <video x-ref="video" autoplay playsinline muted></video>
      </div>

      <template x-if="scanFeedback">
        <div class="ag-scan-feedback" :class="scanOk ? 'ok' : 'err'" x-text="scanFeedback"></div>
      </template>

      <div class="ag-manual-fallback" x-show="cameraError">
        <p style="font-size:13px;margin-bottom:8px;color:rgba(27,67,50,0.6);">
          Caméra indisponible — entrez le code affiché sous le QR de l'établissement :
        </p>
        <input type="text" x-model="manualToken" placeholder="Code de l'établissement">
        <button class="ag-btn ag-btn-gold" @click="submitScan(manualToken)">Valider</button>
      </div>

      <div style="display:flex;gap:10px;margin-top:14px;">
        <button class="ag-btn ag-btn-ghost" @click="closeScanner()" style="flex:1;">Fermer</button>
      </div>
    </div>
  </div>

</body>
</html>
