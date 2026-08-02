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
      <div class="ag-brand-text">
        <span class="ag-brand-title" x-text="agent.nom ? ('Bonjour, ' + agent.nom) : 'Espace agent'"></span>
        <span class="ag-brand-sub">Bienvenue dans votre espace agent</span>
      </div>
    </div>
    <div class="ag-header-actions">
      <a href="<?= $base_url ?>/agent/profile" class="ag-icon-btn" title="Mon profil">
        <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
      </a>
      <button class="ag-logout" @click="logout()">
        <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>
        Déconnexion
      </button>
    </div>
  </header>

  <div class="ag-container">

    <!-- ═══ Vue : Accueil ═══ -->
    <template x-if="activeView === 'home'">
      <div>
        <button class="ag-scan-btn" @click="openScanner()">
          <span class="ag-scan-icon">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h4v4H4V4zm0 12h4v4H4v-4zm12-12h4v4h-4V4zm0 5h4v11h-4V9zM9 4h1v6H9V4zM4 11h6v1H4v-1zm5 3h1v6H9v-6zm-5 2h1v1H4v-1z"/>
            </svg>
          </span>
          <span class="ag-scan-text">
            <strong>Scanner le QR code d'un établissement</strong>
            <span>Scannez le QR code pour rattacher un établissement et commencer à gagner des primes.</span>
          </span>
          <span class="ag-scan-chevron">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </span>
        </button>

        <div class="ag-progress-grid">
          <template x-for="plan in ['pro','business']" :key="plan">
            <div class="ag-progress-card" :class="plan === 'pro' ? 'is-pro' : 'is-business'">
              <div class="ag-plan-head">
                <span class="ag-plan-pill" x-text="plan === 'pro' ? 'PLAN PRO' : 'PLAN BUSINESS'"></span>
              </div>
              <div class="ag-plan-count" x-text="`${progress[plan]?.count || 0} / ${progress[plan]?.target || 0} abonnements`"></div>
              <div class="ag-progress-bar-track">
                <div class="ag-progress-bar-fill" :style="`width:${Math.min(100, (progress[plan]?.count || 0) / (progress[plan]?.target || 1) * 100)}%`"></div>
              </div>
              <div class="ag-plan-foot">
                <div class="ag-plan-foot-label">Versement</div>
                <div class="ag-plan-foot-amount" x-text="formatFcfa(progress[plan]?.reward)"></div>
                <div class="ag-plan-foot-sub" x-text="`Tous les ${progress[plan]?.target || 0} abonnements`"></div>
              </div>
            </div>
          </template>
        </div>

        <div class="ag-section">
          <div class="ag-section-head">
            <h2>Primes</h2>
            <a href="#" class="ag-section-link" @click.prevent="setView('history')">Voir toutes les primes →</a>
          </div>
          <div class="ag-list">
            <div class="ag-bonus-row">
              <span class="ag-bonus-icon">🏁</span>
              <div class="ag-bonus-body">
                <div class="ag-bonus-title">Premier arrivé</div>
                <div class="ag-bonus-desc" x-text="`Le premier agent à ${bonuses.first_to_5?.target || 5} établissements payants (${bonuses.first_to_5?.progress || 0}/${bonuses.first_to_5?.target || 5})`"></div>
              </div>
              <div class="ag-bonus-side">
                <div class="ag-bonus-amount" x-text="formatFcfa(bonuses.first_to_5?.amount)"></div>
                <span class="ag-badge" :class="{
                    'ag-badge-paid':   bonuses.first_to_5?.status === 'won',
                    'ag-badge-locked': bonuses.first_to_5?.status === 'claimed_by_other',
                    'ag-badge-open':   bonuses.first_to_5?.status === 'open',
                  }" x-text="{ won: 'Remportée', claimed_by_other: 'Déjà prise', open: 'À gagner' }[bonuses.first_to_5?.status] || ''"></span>
              </div>
            </div>
            <div class="ag-bonus-row">
              <span class="ag-bonus-icon">💎</span>
              <div class="ag-bonus-body">
                <div class="ag-bonus-title">Premier client Business</div>
                <div class="ag-bonus-desc">Le tout premier établissement Business que vous faites signer</div>
              </div>
              <div class="ag-bonus-side">
                <div class="ag-bonus-amount" x-text="formatFcfa(bonuses.first_business?.amount)"></div>
                <span class="ag-badge" :class="bonuses.first_business?.status === 'won' ? 'ag-badge-paid' : 'ag-badge-open'"
                  x-text="bonuses.first_business?.status === 'won' ? 'Remportée' : 'À gagner'"></span>
              </div>
            </div>
            <div class="ag-bonus-row">
              <span class="ag-bonus-icon">⚡</span>
              <div class="ag-bonus-body">
                <div class="ag-bonus-title">Conversion rapide</div>
                <div class="ag-bonus-desc" x-text="`Établissement payant dans les ${bonuses.fast_conversion?.days || 7} j après le scan`"></div>
              </div>
              <div class="ag-bonus-side">
                <div class="ag-bonus-amount" x-text="formatFcfa(bonuses.fast_conversion?.amount)"></div>
                <span class="ag-badge" :class="bonuses.fast_conversion?.count > 0 ? 'ag-badge-paid' : 'ag-badge-pending'"
                  x-text="bonuses.fast_conversion?.count > 0 ? ('Gagnée ' + bonuses.fast_conversion.count + ' fois') : 'Pas encore gagnée'"></span>
              </div>
            </div>
            <div class="ag-bonus-row">
              <span class="ag-bonus-icon">🏆</span>
              <div class="ag-bonus-body">
                <div class="ag-bonus-title">Top agent du mois</div>
                <div class="ag-bonus-desc" x-text="`Le plus de référencements payants sur le mois (${bonuses.monthly_top?.rank_referrals || 0} ce mois-ci)`"></div>
                <div class="ag-bonus-desc" x-show="bonuses.monthly_top?.won_last" style="color:var(--mid);">Gagnée le mois dernier</div>
              </div>
              <div class="ag-bonus-side">
                <div class="ag-bonus-amount" x-text="formatFcfa(bonuses.monthly_top?.amount)"></div>
                <span class="ag-badge" :class="bonuses.monthly_top?.rank === 1 ? 'ag-badge-paid' : 'ag-badge-neutral'"
                  x-text="bonuses.monthly_top?.rank ? ('#' + bonuses.monthly_top.rank + ' sur ' + bonuses.monthly_top.total_ranked) : 'Pas classé'"></span>
              </div>
            </div>
          </div>
        </div>

        <div class="ag-section">
          <h2>Établissements rattachés (<span x-text="establishments.length"></span>)</h2>
          <template x-if="establishments.length === 0">
            <div class="ag-empty-card">
              <div class="ag-empty-illu">🏠</div>
              <div class="ag-empty-title">Aucun établissement rattaché pour le moment.</div>
              <div class="ag-empty-sub">Scannez un QR code pour commencer.</div>
            </div>
          </template>
          <template x-if="establishments.length > 0">
            <div class="ag-list">
              <template x-for="e in establishments.slice(0, 3)" :key="e.id">
                <div class="ag-list-row">
                  <span x-text="e.establishment_name"></span>
                  <span x-text="e.plan"></span>
                </div>
              </template>
            </div>
          </template>
          <template x-if="establishments.length > 3">
            <a href="#" class="ag-section-link" style="display:block;margin-top:8px;" @click.prevent="setView('establishments')">Voir les <span x-text="establishments.length"></span> établissements →</a>
          </template>
        </div>
      </div>
    </template>

    <!-- ═══ Vue : Mes établissements ═══ -->
    <template x-if="activeView === 'establishments'">
      <div class="ag-section">
        <h2>Mes établissements (<span x-text="establishments.length"></span>)</h2>
        <div class="ag-list">
          <template x-if="establishments.length === 0">
            <div class="ag-list-empty">Aucun établissement rattaché pour l'instant. Scannez un QR code pour commencer.</div>
          </template>
          <template x-for="e in establishments" :key="e.id">
            <div class="ag-list-row">
              <div>
                <div style="font-weight:600;" x-text="e.establishment_name"></div>
                <div style="font-size:11px;color:rgba(27,67,50,0.5);" x-text="'Rattaché le ' + formatDate(e.linked_at)"></div>
              </div>
              <span class="ag-badge ag-badge-neutral" x-text="e.plan"></span>
            </div>
          </template>
        </div>
      </div>
    </template>

    <!-- ═══ Vue : Historique ═══ -->
    <template x-if="activeView === 'history'">
      <div class="ag-section">
        <h2>Historique</h2>
        <div class="ag-list">
          <template x-if="history.length === 0">
            <div class="ag-list-empty">Aucune activité pour l'instant.</div>
          </template>
          <template x-for="(h, i) in history" :key="i">
            <div class="ag-bonus-row">
              <span class="ag-bonus-icon" x-text="h.icon"></span>
              <div class="ag-bonus-body">
                <div class="ag-bonus-title" x-text="h.title"></div>
                <div class="ag-bonus-desc" x-text="h.desc"></div>
              </div>
              <div class="ag-bonus-side">
                <template x-if="h.amount !== null"><div class="ag-bonus-amount" x-text="formatFcfa(h.amount)"></div></template>
                <div style="font-size:11px;color:rgba(27,67,50,0.45);" x-text="formatDate(h.date)"></div>
              </div>
            </div>
          </template>
        </div>
      </div>
    </template>

    <!-- ═══ Vue : Classement ═══ -->
    <template x-if="activeView === 'ranking'">
      <div class="ag-section">
        <h2>Classement du mois</h2>
        <div class="ag-bonus-desc" style="margin-bottom:12px;" x-text="'Le plus de référencements payants ce mois-ci remporte ' + formatFcfa(bonuses.monthly_top?.amount)"></div>
        <div class="ag-list">
          <template x-if="ranking.length === 0">
            <div class="ag-list-empty">Aucun classement pour l'instant.</div>
          </template>
          <template x-for="(r, i) in ranking" :key="r.agent_id">
            <div class="ag-list-row" :style="r.agent_id === agent.id ? 'background:rgba(201,168,76,0.08);' : ''">
              <span>
                <strong x-text="'#' + (i + 1)"></strong>
                &nbsp;
                <span x-text="r.nom"></span>
                <span x-show="r.agent_id === agent.id" style="color:var(--mid);font-weight:600;"> (vous)</span>
              </span>
              <span x-text="r.cnt + ' référencement' + (r.cnt > 1 ? 's' : '')"></span>
            </div>
          </template>
        </div>
      </div>
    </template>

  </div>

  <nav class="ag-bottom-nav">
    <button class="ag-bnav-item" :class="activeView === 'home' ? 'active' : ''" @click="setView('home')">
      <svg xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7m-9-9v18h6a2 2 0 002-2v-9m-8 9H5a2 2 0 01-2-2v-9"/>
      </svg>
      Accueil
    </button>
    <button class="ag-bnav-item" :class="activeView === 'establishments' ? 'active' : ''" @click="setView('establishments')">
      <svg xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1m-1 4h1m4-4h1m-1 4h1M9 21v-4h6v4"/>
      </svg>
      Mes établissements
    </button>
    <button class="ag-bnav-item" :class="activeView === 'history' ? 'active' : ''" @click="setView('history')">
      <svg xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      Historique
    </button>
    <button class="ag-bnav-item" :class="activeView === 'ranking' ? 'active' : ''" @click="setView('ranking')">
      <svg xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 21h8M12 17v4M7 4h10v4a5 5 0 01-10 0V4zM7 6H4v2a3 3 0 003 3M17 6h3v2a3 3 0 01-3 3"/>
      </svg>
      Classement
    </button>
  </nav>

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
          Caméra indisponible. Entrez le code affiché sous le QR de l'établissement :
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
