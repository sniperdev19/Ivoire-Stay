<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php $pageJs  = 'saas-settings'; ?>
<?php $pageCss = 'saas-settings'; ?>

<div x-data="settingsPage('<?= $base_url ?>')" x-init="init()">

  <!-- En-tête -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <div>
      <h1 class="stg-page-title">Paramètres</h1>
      <p class="stg-page-sub">Gérez les informations de votre établissement et abonnement</p>
    </div>
  </div>

  <!-- Navigation onglets -->
  <div class="stg-tabs">
    <button @click="activeTab='general'"
            :class="activeTab==='general' ? 'stg-tab-active' : ''"
            class="stg-tab">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
      Général
    </button>
    <button @click="activeTab='subscription'"
            :class="activeTab==='subscription' ? 'stg-tab-active' : ''"
            class="stg-tab">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
      Abonnement
    </button>
    <button @click="activeTab='account'"
            :class="activeTab==='account' ? 'stg-tab-active' : ''"
            class="stg-tab">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      Compte
    </button>
  </div>

  <!-- Bannière nouvel établissement -->
  <div x-show="isNewEstab && !loading" class="stg-welcome" x-cloak>
    <div class="stg-welcome-emoji">👋</div>
    <div>
      <div class="stg-welcome-title">Bienvenue sur Ivoire Stay !</div>
      <div class="stg-welcome-desc">Commencez par configurer votre établissement. Remplissez le formulaire ci-dessous, puis cliquez sur « Créer l'établissement ».</div>
    </div>
  </div>

  <!-- ════════════════════════════════════════════════════════
       ONGLET : Général
       ════════════════════════════════════════════════════════ -->
  <div x-show="activeTab==='general'">
    <div class="saas-card" style="padding:24px;">

      <div class="stg-section-head">
        <div class="stg-section-icon">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        </div>
        <div>
          <h3 class="stg-section-title" x-text="isNewEstab ? 'Créer votre établissement' : 'Informations de l\'établissement'"></h3>
          <p class="stg-section-sub">Ces informations apparaissent sur votre vitrine publique</p>
        </div>
      </div>

      <div class="stg-divider"></div>

      <div class="stg-form-grid">
        <div class="stg-col-2">
          <label class="saas-label">Nom de l'établissement</label>
          <input class="saas-input" x-model="form.name" placeholder="Ex : Hôtel Le Président">
        </div>
        <div>
          <label class="saas-label">Type</label>
          <select class="saas-input" x-model="form.type">
            <option value="hotel">Hôtel</option>
            <option value="residence">Résidence</option>
            <option value="villa">Villa</option>
          </select>
        </div>
        <div>
          <label class="saas-label">Ville</label>
          <input class="saas-input" x-model="form.city" placeholder="Ex : Abidjan">
        </div>
        <div>
          <label class="saas-label">Adresse</label>
          <input class="saas-input" x-model="form.address" placeholder="Ex : Plateau, Rue des Jardins">
        </div>
        <div>
          <label class="saas-label">Téléphone</label>
          <input class="saas-input" x-model="form.phone" placeholder="+225 07 00 00 00 00">
        </div>
        <div>
          <label class="saas-label">Email</label>
          <input class="saas-input" type="email" x-model="form.email" placeholder="contact@hotel.ci">
        </div>
        <div class="stg-col-2">
          <label class="saas-label">Description</label>
          <textarea class="saas-input" rows="4" x-model="form.description" placeholder="Décrivez votre établissement en quelques phrases…"></textarea>
        </div>
        <div>
          <label class="saas-label">Site web</label>
          <input class="saas-input" x-model="form.website" placeholder="https://…">
        </div>
      </div>

      <div class="stg-feedback">
        <template x-if="saveSuccess">
          <div class="stg-alert stg-alert-success">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            Modifications enregistrées avec succès !
          </div>
        </template>
        <template x-if="saveError">
          <div class="stg-alert stg-alert-error">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span x-text="saveError"></span>
          </div>
        </template>
      </div>

      <div class="stg-form-actions">
        <button class="btn-saas-primary" @click="saveGeneral()" :disabled="saving">
          <div x-show="saving" class="stg-btn-spinner"></div>
          <span x-show="!saving" x-text="isNewEstab ? 'Créer l\'établissement' : 'Enregistrer les modifications'"></span>
        </button>
      </div>
    </div>
  </div>

  <!-- ════════════════════════════════════════════════════════
       ONGLET : Abonnement
       ════════════════════════════════════════════════════════ -->
  <div x-show="activeTab==='subscription'">

    <!-- Bannière retour paiement -->
    <div x-show="paymentSuccess" x-cloak
         style="display:flex;align-items:center;gap:12px;background:#d1fae5;border:1px solid #6ee7b7;color:#065f46;border-radius:10px;padding:14px 18px;margin-bottom:20px;font-size:.93rem;">
      <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <span>Paiement confirmé ! Votre abonnement est actif.</span>
    </div>
    <div x-show="paymentError" x-cloak
         style="display:flex;align-items:center;gap:12px;background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;border-radius:10px;padding:14px 18px;margin-bottom:20px;font-size:.93rem;">
      <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <span x-text="paymentError"></span>
    </div>

    <!-- Hero plan actuel -->
    <div class="stg-sub-hero">
      <div>
        <div class="stg-sub-pill">Plan actuel</div>
        <div class="stg-sub-plan" x-text="plans[subscription?.plan]?.name ?? planLabel(subscription?.plan)"></div>
        <div class="stg-sub-valid">
          Valide jusqu'au <strong x-text="formatDate(subscription?.expires_at)"></strong>
        </div>
      </div>
      <div class="stg-sub-right">
        <div class="stg-sub-stat">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
          Chambres&nbsp;: <strong x-text="establishment?.rooms_count ?? 0"></strong>
        </div>
        <a href="<?= $base_url ?>/saas/checkout" class="btn-saas-primary">
          Gérer mon abonnement →
        </a>
      </div>
    </div>

    <!-- Cards des 3 plans -->
    <div class="stg-plans">
      <template x-for="p in ['starter','pro','business']" :key="p">
        <div class="stg-plan" :class="subscription?.plan === p ? 'stg-plan-current' : ''">
          <div class="stg-plan-head">
            <div class="stg-plan-name" x-text="plans[p]?.name ?? planLabel(p)"></div>
            <div x-show="subscription?.plan === p" class="badge badge-success">Actif</div>
          </div>
          <div class="stg-plan-price">
            <span x-text="(plans[p]?.prices?.monthly ?? 0) === 0 ? 'Gratuit' : ((plans[p]?.prices?.monthly ?? 0).toLocaleString('fr-FR') + ' FCFA/mois')"></span>
          </div>
          <div class="stg-plan-yr" x-show="(plans[p]?.prices?.yearly ?? 0) > 0">
            ou <span x-text="(plans[p]?.prices?.yearly ?? 0).toLocaleString('fr-FR')"></span> FCFA/an
          </div>
          <div class="stg-plan-rooms">
            <span x-text="(plans[p]?.max_rooms ?? 0) >= 999999 ? 'Chambres illimitées' : ('Jusqu\'à ' + (plans[p]?.max_rooms ?? 0) + ' chambres')"></span>
          </div>
          <ul class="stg-plan-features">
            <template x-for="[feat, enabled] in Object.entries(plans[p]?.features ?? {})" :key="feat">
              <li>
                <span class="stg-fi" :class="enabled ? 'stg-fi-ok' : 'stg-fi-no'" x-text="enabled ? '✓' : '✗'"></span>
                <span :class="enabled ? 'stg-ft-ok' : 'stg-ft-no'" x-text="featureLabels[feat] ?? feat"></span>
              </li>
            </template>
          </ul>
          <div>
            <template x-if="subscription?.plan !== p">
              <button class="btn-saas-primary" style="width:100%;" @click="window.location='<?= $base_url ?>/saas/checkout?plan=' + p">Choisir ce plan</button>
            </template>
            <template x-if="subscription?.plan === p">
              <div class="stg-plan-active">✓ Plan actif</div>
            </template>
          </div>
        </div>
      </template>
    </div>
  </div>

  <!-- ════════════════════════════════════════════════════════
       ONGLET : Compte
       ════════════════════════════════════════════════════════ -->
  <div x-show="activeTab==='account'">
    <div class="saas-card" style="padding:24px;">

      <!-- Avatar + infos utilisateur -->
      <div class="stg-account-hero">
        <div class="stg-avatar">
          <span x-text="(JSON.parse(localStorage.getItem('user')||'{}').name||'U').split(' ').map(s=>s[0]).slice(0,2).join('')"></span>
        </div>
        <div>
          <div class="stg-account-name"  x-text="JSON.parse(localStorage.getItem('user')||'{}').name"></div>
          <div class="stg-account-email" x-text="JSON.parse(localStorage.getItem('user')||'{}').email"></div>
          <div class="stg-account-role">
            <span class="badge" :class="planColor(JSON.parse(localStorage.getItem('user')||'{}').role)">
              <span x-text="JSON.parse(localStorage.getItem('user')||'{}').role"></span>
            </span>
          </div>
        </div>
      </div>

      <!-- Panneaux sécurité / danger -->
      <div class="stg-panels">
        <div class="stg-panel stg-panel-security">
          <div class="stg-panel-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          </div>
          <div class="stg-panel-title">Sécurité</div>
          <div class="stg-panel-desc">La modification du mot de passe sera disponible dans une prochaine mise à jour.</div>
          <button class="btn-saas-secondary" disabled style="opacity:0.5;cursor:not-allowed;">Changer le mot de passe</button>
        </div>

        <div class="stg-panel stg-panel-danger">
          <div class="stg-panel-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          </div>
          <div class="stg-panel-title">Zone de danger</div>
          <div class="stg-panel-desc">Supprimer définitivement votre compte et toutes les données associées.</div>
          <button class="btn-saas-danger" disabled style="opacity:0.5;cursor:not-allowed;font-size:12px;">Contacter le support</button>
        </div>
      </div>
    </div>
  </div>

</div>
