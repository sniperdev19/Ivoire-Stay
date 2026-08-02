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

  <div class="ag-shell">

  <header class="ag-header">
    <div class="ag-brand">
      <div class="ag-brand-sq" x-text="initials"></div>
      <div class="ag-brand-text">
        <span class="ag-brand-title" x-text="agent.nom ? ('Bonjour, ' + agent.nom) : 'Espace agent'"></span>
        <span class="ag-brand-sub">Bienvenue dans votre espace agent</span>
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

  <div class="ag-container">

    <!-- ═══ Vue : Accueil ═══ -->
    <template x-if="activeView === 'home'">
      <div>
        <div class="ag-hero">
          <button type="button" class="ag-hero-qr" @click="openScanner()">
            <span class="ag-hero-qr-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3.75" y="3.75" width="6" height="6" rx="1.2"/>
                <rect x="3.75" y="14.25" width="6" height="6" rx="1.2"/>
                <rect x="14.25" y="3.75" width="6" height="6" rx="1.2"/>
                <path d="M14.25 14.25h2.25v2.25h-2.25zM19.5 14.25h1.5v1.5h-1.5zM14.25 19.5h1.5v1.5h-1.5zM17.25 17.25h1.5v1.5h-1.5zM19.5 19.5h1.5v1.5h-1.5z"/>
              </svg>
            </span>
            <span class="ag-hero-qr-pill">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/>
                <circle cx="12" cy="13" r="4"/>
              </svg>
              Scanner
            </span>
            <span class="ag-hero-qr-hint">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 5l7 7-7 7"/>
              </svg>
            </span>
          </button>
          <p class="ag-hero-caption">Scannez le QR code d'un établissement pour le rattacher et commencer à gagner des primes.</p>
        </div>

        <div class="ag-quick-grid">
          <button type="button" class="ag-quick-item" @click="setView('establishments')">
            <span class="ag-quick-icon ag-quick-icon-a">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1m-1 4h1m4-4h1m-1 4h1M9 21v-4h6v4"/>
              </svg>
            </span>
            <span class="ag-quick-label">Établissements</span>
          </button>
          <button type="button" class="ag-quick-item" @click="setView('history')">
            <span class="ag-quick-icon ag-quick-icon-b">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </span>
            <span class="ag-quick-label">Historique</span>
          </button>
          <button type="button" class="ag-quick-item" @click="setView('ranking')">
            <span class="ag-quick-icon ag-quick-icon-c">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 21h8M12 17v4M7 4h10v4a5 5 0 01-10 0V4zM7 6H4v2a3 3 0 003 3M17 6h3v2a3 3 0 01-3 3"/>
              </svg>
            </span>
            <span class="ag-quick-label">Classement</span>
          </button>
          <button type="button" class="ag-quick-item" @click="setView('bonuses')">
            <span class="ag-quick-icon ag-quick-icon-e">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 12 20 22 4 22 4 12"></polyline>
                <rect x="2" y="7" width="20" height="5"></rect>
                <line x1="12" y1="22" x2="12" y2="7"></line>
                <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path>
                <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path>
              </svg>
            </span>
            <span class="ag-quick-label">Primes</span>
          </button>
          <a href="<?= $base_url ?>/agent/profile" class="ag-quick-item">
            <span class="ag-quick-icon ag-quick-icon-d">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
            </span>
            <span class="ag-quick-label">Profil</span>
          </a>
        </div>

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
          <h2>Établissements rattachés (<span x-text="establishments.length"></span>)</h2>
          <template x-if="establishments.length === 0">
            <div class="ag-empty-card">
              <div class="ag-empty-illu">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1m-1 4h1m4-4h1m-1 4h1M9 21v-4h6v4"/>
                </svg>
              </div>
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
        <button type="button" class="ag-back-btn" @click="setView('home')">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
          Retour
        </button>
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

    <!-- ═══ Vue : Primes ═══ -->
    <template x-if="activeView === 'bonuses'">
      <div class="ag-section">
        <button type="button" class="ag-back-btn" @click="setView('home')">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
          Retour
        </button>
        <h2>Primes</h2>
        <div class="ag-bonus-cards">
          <div class="ag-bonus-card ag-bonus-card-a">
            <span class="ag-bonus-card-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 3v18M5 4c1.5-1 3.5-1 5 0s3.5 1 5 0 3.5-1 5 0v9c-1.5 1-3.5 1-5 0s-3.5-1-5 0-3.5 1-5 0V4z"/>
              </svg>
            </span>
            <div class="ag-bonus-card-label">Premier arrivé</div>
            <div class="ag-bonus-card-amount" x-text="formatFcfa(bonuses.first_to_5?.amount)"></div>
            <div class="ag-bonus-card-desc" x-text="`${bonuses.first_to_5?.progress || 0}/${bonuses.first_to_5?.target || 5} établissements payants`"></div>
            <span class="ag-bonus-card-badge" x-text="{ won: 'Remportée', claimed_by_other: 'Déjà prise', open: 'À gagner' }[bonuses.first_to_5?.status] || ''"></span>
          </div>
          <div class="ag-bonus-card ag-bonus-card-c">
            <span class="ag-bonus-card-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 3h12l3 5-9 13L3 8l3-5z"/>
                <path d="M3 8h18M9 3l3 5 3-5M9 8l3 13 3-13"/>
              </svg>
            </span>
            <div class="ag-bonus-card-label">Premier client Business</div>
            <div class="ag-bonus-card-amount" x-text="formatFcfa(bonuses.first_business?.amount)"></div>
            <div class="ag-bonus-card-desc">1er établissement Business signé</div>
            <span class="ag-bonus-card-badge" x-text="bonuses.first_business?.status === 'won' ? 'Remportée' : 'À gagner'"></span>
          </div>
          <div class="ag-bonus-card ag-bonus-card-d">
            <span class="ag-bonus-card-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M13 2L3 14h7l-1 8 11-14h-7l1-8z"/>
              </svg>
            </span>
            <div class="ag-bonus-card-label">Conversion rapide</div>
            <div class="ag-bonus-card-amount" x-text="formatFcfa(bonuses.fast_conversion?.amount)"></div>
            <div class="ag-bonus-card-desc" x-text="`Payant sous ${bonuses.fast_conversion?.days || 7} j après le scan`"></div>
            <span class="ag-bonus-card-badge" x-text="bonuses.fast_conversion?.count > 0 ? ('Gagnée ' + bonuses.fast_conversion.count + ' fois') : 'Pas encore gagnée'"></span>
          </div>
          <div class="ag-bonus-card ag-bonus-card-e">
            <span class="ag-bonus-card-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8 21h8M12 17v4M7 4h10v4a5 5 0 01-10 0V4zM7 6H4v2a3 3 0 003 3M17 6h3v2a3 3 0 01-3 3"/>
              </svg>
            </span>
            <div class="ag-bonus-card-label">Top agent du mois</div>
            <div class="ag-bonus-card-amount" x-text="formatFcfa(bonuses.monthly_top?.amount)"></div>
            <div class="ag-bonus-card-desc" x-text="`${bonuses.monthly_top?.rank_referrals || 0} référencement(s) ce mois-ci`"></div>
            <span class="ag-bonus-card-badge" x-text="bonuses.monthly_top?.rank ? ('#' + bonuses.monthly_top.rank + ' sur ' + bonuses.monthly_top.total_ranked) : 'Pas classé'"></span>
          </div>
        </div>
      </div>
    </template>

    <!-- ═══ Vue : Historique ═══ -->
    <template x-if="activeView === 'history'">
      <div class="ag-section">
        <button type="button" class="ag-back-btn" @click="setView('home')">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
          Retour
        </button>
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
        <button type="button" class="ag-back-btn" @click="setView('home')">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
          Retour
        </button>
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

  </div>

  <!-- ═══ Modal scan QR ═══ -->
  <div class="ag-modal-bg" x-show="showScanner" x-cloak @click.self="closeScanner()">
    <div class="ag-modal">
      <h3>Scanner le QR code</h3>

      <div class="ag-video-wrap" x-show="!cameraError && !showManualEntry">
        <video x-ref="video" autoplay playsinline muted></video>
      </div>

      <template x-if="scanFeedback">
        <div class="ag-scan-feedback" :class="scanOk ? 'ok' : 'err'" x-text="scanFeedback"></div>
      </template>

      <div class="ag-manual-fallback" x-show="cameraError || showManualEntry">
        <p style="font-size:13px;margin-bottom:8px;color:rgba(27,67,50,0.6);">
          <template x-if="cameraError">
            <span>Caméra indisponible. Entrez le code affiché sous le QR de l'établissement :</span>
          </template>
          <template x-if="!cameraError">
            <span>Établissement éloigné ? Entrez le code qu'il vous a transmis :</span>
          </template>
        </p>
        <input type="text" x-model="manualToken" placeholder="Code de l'établissement">
        <button class="ag-btn ag-btn-gold" @click="submitScan(manualToken)">Valider</button>
      </div>

      <button type="button" class="ag-btn ag-btn-ghost" x-show="!cameraError" @click="showManualEntry = !showManualEntry" style="width:100%;margin-top:10px;font-size:13px;">
        <span x-text="showManualEntry ? 'Revenir au scan caméra' : 'Saisir le code manuellement'"></span>
      </button>

      <div style="display:flex;gap:10px;margin-top:14px;">
        <button class="ag-btn ag-btn-ghost" @click="closeScanner()" style="flex:1;">Fermer</button>
      </div>
    </div>
  </div>

</body>
</html>
