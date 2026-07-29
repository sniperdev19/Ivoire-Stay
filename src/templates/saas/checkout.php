<?php
$base_url = $base_url ?? rtrim(APP_URL, '/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Paiement abonnement – Afristay</title>

  <meta name="theme-color" content="#1B4332">
  <link rel="icon" href="<?= $base_url ?>/assets/icons/icon-192.png" type="image/png">

  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/fonts.css">

  <link rel="stylesheet" href="<?= $base_url ?>/assets/css/pages/checkout.css">
  <script src="<?= $base_url ?>/assets/js/pages/checkout.js"></script>
  <script defer src="<?= $base_url ?>/assets/js/alpine.min.js"></script>
</head>
<body>

<div class="ck-page" x-data="checkoutPage('<?= $base_url ?>')" x-init="init()">

  <!-- ══════════════════════════════════════════
       PANNEAU GAUCHE — Résumé du plan
  ══════════════════════════════════════════ -->
  <div class="ck-left">
    <div class="ck-left-ghost" x-text="current.name"></div>
    <div class="ck-left-radial"></div>

    <!-- Brand -->
    <div class="ck-brand">
      <a class="ck-brand-inner" href="<?= $base_url ?>/">
        <div class="ck-brand-sq">AS</div>
        <div class="ck-brand-text">
          <strong>Afri <span style="color:#C9A84C;">Stay</span></strong>
          <span>SaaS Hôtelier</span>
        </div>
      </a>
      <a class="ck-back-link" href="<?= $base_url ?>/saas/settings">← Paramètres</a>
    </div>

    <!-- Plan Info -->
    <div class="ck-plan-body">
      <div class="ck-plan-rule"></div>
      <div class="ck-plan-tag">Abonnement sélectionné</div>

      <div class="ck-plan-name">
        Plan<br><em x-text="current.name"></em>
      </div>

      <p class="ck-plan-tagline" x-text="current.tagline"></p>

      <ul class="ck-features">
        <template x-for="feat in current.features" :key="feat">
          <li class="ck-feature">
            <span class="ck-feature-check">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#C9A84C" stroke-width="3">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
            </span>
            <span x-text="feat"></span>
          </li>
        </template>
      </ul>

      <!-- Prix -->
      <div class="ck-price-block">
        <div x-show="prorationCredit > 0" style="font-size:12px;color:rgba(255,255,255,0.55);text-decoration:line-through;margin-bottom:2px;" x-text="new Intl.NumberFormat('fr-CI').format(fullPrice) + ' FCFA'"></div>
        <div class="ck-price-label">Total à régler</div>
        <div class="ck-price-row">
          <span class="ck-price-amount" x-text="new Intl.NumberFormat('fr-CI').format(total)"></span>
          <span class="ck-price-unit">FCFA</span>
        </div>
        <div class="ck-price-period" x-text="periodLabel"></div>
        <div class="ck-savings-badge" x-show="billing === 'yearly'">
          <svg xmlns="http://www.w3.org/2000/svg" style="width:12px;height:12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
          </svg>
          Économie de <span x-text="new Intl.NumberFormat('fr-CI').format(savings) + ' FCFA'"></span>
        </div>
        <div class="ck-savings-badge" x-show="prorationCredit > 0" style="margin-top:6px;">
          <svg xmlns="http://www.w3.org/2000/svg" style="width:12px;height:12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
          </svg>
          Crédit de prorata de <span x-text="new Intl.NumberFormat('fr-CI').format(prorationCredit) + ' FCFA'"></span> appliqué
        </div>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════
       PANNEAU DROIT — Formulaire de paiement
  ══════════════════════════════════════════ -->
  <div class="ck-right">
    <div class="ck-form-wrap">

      <!-- Header -->
      <div class="ck-form-eyebrow">
        <div class="ck-form-eyebrow-line"></div>
        <span>Paiement · Abonnement</span>
        <div class="ck-form-eyebrow-line"></div>
      </div>

      <h1 class="ck-form-title">Finalisez votre<br><em>abonnement.</em></h1>
      <p class="ck-form-sub">Paiement sécurisé, activation instantanée. Annulation à tout moment depuis vos paramètres.</p>

      <!-- Erreur -->
      <template x-if="error">
        <div class="ck-alert">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span x-text="error"></span>
        </div>
      </template>

      <!-- ① Facturation -->
      <div class="ck-label">Période de facturation</div>
      <div class="ck-billing-toggle">
        <button
          type="button"
          class="ck-billing-opt"
          :class="billing === 'monthly' ? 'active' : ''"
          @click="setBilling('monthly')"
        >
          <span class="ck-billing-opt-name">Mensuel</span>
          <span class="ck-billing-opt-price" x-text="new Intl.NumberFormat('fr-CI').format(current.prices.monthly) + ' FCFA/mois'"></span>
        </button>
        <button
          type="button"
          class="ck-billing-opt"
          :class="billing === 'yearly' ? 'active' : ''"
          @click="setBilling('yearly')"
          style="position:relative;"
        >
          <span class="ck-billing-discount">−20%</span>
          <span class="ck-billing-opt-name">Annuel</span>
          <span class="ck-billing-opt-price" x-text="new Intl.NumberFormat('fr-CI').format(current.monthlyEq.yearly) + ' FCFA/mois'"></span>
        </button>
      </div>

      <!-- ② Mode de paiement -->
      <div class="ck-label">Mode de paiement</div>
      <div class="ck-pay-opts">

        <div class="ck-pay-opt" :class="payMethod === 'orange' ? 'selected' : ''" @click="payMethod = 'orange'">
          <div class="ck-pay-radio"><div class="ck-pay-radio-dot"></div></div>
          <div class="ck-pay-icon" style="background:#FF6B00;">OM</div>
          <div class="ck-pay-info">
            <div class="ck-pay-name">Orange Money</div>
            <div class="ck-pay-sub">Paiement instantané via Orange CI</div>
          </div>
        </div>

        <div class="ck-pay-opt" :class="payMethod === 'wave' ? 'selected' : ''" @click="payMethod = 'wave'">
          <div class="ck-pay-radio"><div class="ck-pay-radio-dot"></div></div>
          <div class="ck-pay-icon" style="background:#00B9F2;">WV</div>
          <div class="ck-pay-info">
            <div class="ck-pay-name">Wave</div>
            <div class="ck-pay-sub">Sans frais de transaction</div>
          </div>
        </div>

        <div class="ck-pay-opt" :class="payMethod === 'mtn' ? 'selected' : ''" @click="payMethod = 'mtn'">
          <div class="ck-pay-radio"><div class="ck-pay-radio-dot"></div></div>
          <div class="ck-pay-icon" style="background:#FFCB00;color:#1B1B1B;">MTN</div>
          <div class="ck-pay-info">
            <div class="ck-pay-name">MTN Mobile Money</div>
            <div class="ck-pay-sub">Disponible 24h/24</div>
          </div>
        </div>

      </div>

      <!-- ③ Récap total -->
      <div class="ck-summary">
        <div class="ck-summary-left">
          <div class="ck-summary-label-main" x-text="'Plan ' + current.name"></div>
          <div class="ck-summary-desc" x-text="billing === 'yearly' ? 'Facturation annuelle' : 'Facturation mensuelle'"></div>
          <div x-show="prorationCredit > 0" style="font-size:12px;color:#16A34A;font-weight:600;margin-top:4px;" x-text="'Crédit prorata : -' + new Intl.NumberFormat('fr-CI').format(prorationCredit) + ' FCFA'"></div>
        </div>
        <div>
          <div class="ck-summary-amount" x-text="new Intl.NumberFormat('fr-CI').format(total) + ' FCFA'"></div>
          <div class="ck-summary-period" x-text="billing === 'yearly' ? '/ an' : '/ mois'"></div>
        </div>
      </div>

      <!-- Bouton payer -->
      <button
        type="button"
        class="ck-btn-submit"
        @click="submit()"
        :disabled="loading"
      >
        <div class="ck-spinner" x-show="loading"></div>
        <span x-show="!loading">
          Payer
          <span x-text="new Intl.NumberFormat('fr-CI').format(total) + ' FCFA'"></span>
          →
        </span>
        <span x-show="loading">Redirection en cours…</span>
      </button>

      <!-- Légal -->
      <p class="ck-legal">
        En procédant au paiement, vous acceptez nos
        <a href="<?= $base_url ?>/conditions">conditions d'utilisation</a>.
        Votre abonnement est activé immédiatement après confirmation du paiement.
        Annulation possible à tout moment depuis vos paramètres.
      </p>

    </div>
  </div>

</div>

</body>
</html>
