<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php $pageJs = 'admin-settings'; ?>
<?php $pageCss = 'saas-settings'; ?>
<!-- Onglet actif en indigo plutôt qu'en or : identité admin distincte de la
     vitrine/du SaaS hôtelier (cf. admin.css), .stg-side-item-active vient de
     saas-settings.css et charge APRÈS admin.css (donc gagne la cascade sans
     ce correctif local). -->
<style>
.stg-side-item-active { background: rgba(99,102,241,0.1) !important; color: #4338CA !important; }
.stg-side-item-active svg { color: #6366F1 !important; }
.admin-settings-page .stg-section-icon { background: rgba(99,102,241,0.1) !important; }
.admin-settings-page .stg-section-icon svg { color: #6366F1 !important; }
.admin-settings-page .stg-switch-on { background: linear-gradient(135deg,#6366F1,#4338CA) !important; }
.admin-settings-page .stg-group-label { color: #6366F1 !important; }
.admin-settings-page .stg-carousel-dot-active { background: #6366F1 !important; }
</style>
<div class="admin-settings-page" x-data="adminSettingsPage('<?= $base_url ?>')" x-init="init()">

  <div style="margin-bottom:20px;">
    <h1 style="font-size:22px;font-weight:700;color:#111827;margin:0;">Paramètres</h1>
    <p style="margin:6px 0 0;color:#9CA3AF;">Réglages de la plateforme et de votre compte</p>
  </div>

  <div class="stg-layout">

    <!-- Navigation latérale -->
    <nav class="stg-side-nav">
      <button @click="activeTab='profile'" :class="activeTab==='profile' ? 'stg-side-item-active' : ''" class="stg-side-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        <span>Mon profil</span>
      </button>
      <button @click="activeTab='platform'" :class="activeTab==='platform' ? 'stg-side-item-active' : ''" class="stg-side-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <span>Réglages plateforme</span>
      </button>
      <button @click="activeTab='backups'" :class="activeTab==='backups' ? 'stg-side-item-active' : ''" class="stg-side-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
        <span>Sauvegarde</span>
      </button>
    </nav>

    <!-- Contenu -->
    <div class="stg-content">

    <!-- ════════════════════════════════════════════════════════
         ONGLET : Mon profil
         ════════════════════════════════════════════════════════ -->
    <div x-show="activeTab==='profile'">
      <div class="saas-card" style="padding:24px;margin-bottom:16px;">
        <div class="stg-account-hero">
          <div class="stg-avatar-wrap">
            <div class="stg-avatar" :style="userAvatarUrl ? ('background-image:url(\'' + userAvatarUrl + '\')') : ''">
              <span x-show="!userAvatarUrl" x-text="(currentUser.name||'A').split(' ').map(s=>s[0]).slice(0,2).join('')"></span>
            </div>
            <label class="stg-avatar-edit" :class="avatarUploading ? 'stg-avatar-edit-loading' : ''" title="Changer la photo">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              <input type="file" accept="image/png,image/jpeg,image/webp" style="display:none" :disabled="avatarUploading" @change="uploadAvatar($event)">
            </label>
            <button type="button" class="stg-avatar-remove" x-show="userAvatarUrl" @click="removeAvatar()" title="Supprimer la photo">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
          <div>
            <div class="stg-account-name" x-text="currentUser.name"></div>
            <div class="stg-account-email" x-text="currentUser.email"></div>
          </div>
        </div>
        <p x-show="avatarError" class="stg-payment-toggle-desc" style="color:#DC2626;margin-top:10px;" x-text="avatarError"></p>

        <div style="display:grid;gap:14px;grid-template-columns:1fr 1fr;margin-top:22px;">
          <div>
            <label class="saas-label">Nom complet</label>
            <input class="saas-input" x-model="profileForm.name" placeholder="Votre nom">
          </div>
          <div>
            <label class="saas-label">Téléphone <span class="stg-label-optional">(optionnel)</span></label>
            <input class="saas-input" x-model="profileForm.phone" placeholder="+225 07 00 00 00 00">
          </div>
        </div>
        <div x-show="profileError" class="stg-alert stg-alert-error" style="margin-top:14px;">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span x-text="profileError"></span>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:16px;">
          <button class="btn-saas-primary" @click="saveProfile()" :disabled="profileSaving">
            <span x-show="!profileSaving">Enregistrer le profil</span>
            <span x-show="profileSaving">Enregistrement…</span>
          </button>
        </div>
      </div>

      <div class="stg-panels" style="grid-template-columns:1fr;">
        <div class="stg-panel stg-panel-security">
          <div class="stg-panel-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          </div>
          <div class="stg-panel-title">Sécurité</div>
          <div class="stg-panel-desc">Modifiez votre mot de passe pour sécuriser l'accès à votre compte.</div>
          <button class="btn-saas-secondary" @click="openPasswordModal()">Changer le mot de passe</button>
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════
         ONGLET : Réglages plateforme
         ════════════════════════════════════════════════════════ -->
    <div x-show="activeTab==='platform'">
      <div x-show="settingsLoading" style="color:#9CA3AF;font-size:13px;">Chargement…</div>

      <div x-show="!settingsLoading">

        <div class="stg-section-head" style="margin-bottom:16px;">
          <div class="stg-section-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          </div>
          <div style="flex:1;">
            <h3 class="stg-section-title" x-text="settingsSectionLabels[settingsSections[settingsCardIndex]]"></h3>
            <p class="stg-section-sub">S'appliquent immédiatement à toute la plateforme</p>
          </div>
        </div>

        <!-- Carrousel : navigation entre les cartes -->
        <div class="stg-carousel-nav">
          <button type="button" class="btn-saas-secondary stg-carousel-btn" @click="settingsCardIndex = Math.max(0, settingsCardIndex - 1)" :disabled="settingsCardIndex === 0">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            <span>Précédent</span>
          </button>

          <div class="stg-carousel-dots">
            <template x-for="(key, i) in settingsSections" :key="key">
              <button type="button" class="stg-carousel-dot" :class="i === settingsCardIndex ? 'stg-carousel-dot-active' : ''"
                      @click="settingsCardIndex = i" :aria-label="settingsSectionLabels[key]" :title="settingsSectionLabels[key]"></button>
            </template>
          </div>

          <button type="button" class="btn-saas-secondary stg-carousel-btn" @click="settingsCardIndex = Math.min(settingsSections.length - 1, settingsCardIndex + 1)" :disabled="settingsCardIndex === settingsSections.length - 1">
            <span x-show="settingsCardIndex < settingsSections.length - 1">Suivant : <span x-text="settingsSectionLabels[settingsSections[settingsCardIndex + 1]]"></span></span>
            <span x-show="settingsCardIndex === settingsSections.length - 1">Dernière section</span>
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
          </button>
        </div>

        <!-- Carte : Agents commerciaux -->
        <div class="saas-card" style="padding:24px;margin-bottom:16px;" x-show="settingsSections[settingsCardIndex] === 'agents'">
          <div class="stg-payment-toggle">
            <div class="stg-payment-toggle-info">
              <div class="stg-payment-toggle-title">Agents commerciaux</div>
              <p class="stg-payment-toggle-desc">Inscription, connexion et scan QR. Coupe toute la fonctionnalité si désactivé.</p>
            </div>
            <button type="button" class="stg-switch" :class="settings.agents_enabled ? 'stg-switch-on' : ''"
              @click="settings.agents_enabled = !settings.agents_enabled"
              :aria-pressed="settings.agents_enabled ? 'true' : 'false'" aria-label="Activer ou désactiver les agents commerciaux">
              <span class="stg-switch-dot"></span>
            </button>
          </div>
        </div>

        <!-- Carte : Coordonnées de contact -->
        <div class="saas-card" style="padding:24px;margin-bottom:16px;" x-show="settingsSections[settingsCardIndex] === 'contact'">
          <div class="stg-group-label">Coordonnées de contact du site</div>
          <p class="stg-section-sub" style="margin-bottom:14px;">Affichées en pied de page et sur /contact</p>
          <div class="stg-form-grid">
            <div>
              <label class="saas-label">Email</label>
              <div class="stg-input-wrap">
                <svg class="stg-input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <input class="saas-input stg-input-has-icon" type="email" x-model="settings.contact_email" placeholder="contact@afristay.ci">
              </div>
            </div>
            <div>
              <label class="saas-label">Téléphone</label>
              <div class="stg-input-wrap">
                <svg class="stg-input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                <input class="saas-input stg-input-has-icon" x-model="settings.contact_phone" placeholder="+225 01 61 95 90 80">
              </div>
            </div>
          </div>
        </div>

        <!-- Carte : Prix des abonnements -->
        <div class="saas-card" style="padding:24px;margin-bottom:16px;" x-show="settingsSections[settingsCardIndex] === 'prices'">
          <div class="stg-group-label">Prix des abonnements</div>
          <p class="stg-section-sub" style="margin-bottom:14px;">S'appliquent aux prochains paiements (page /tarifs, checkout, espace hôtelier), jamais aux abonnements déjà payés</p>

          <div class="stg-group-label">Plan Pro</div>
          <div class="stg-form-grid" style="margin-bottom:22px;">
            <div>
              <label class="saas-label">Prix mensuel</label>
              <input class="saas-input" type="number" min="1" step="500" x-model.number="settings.plan_price_pro_monthly" @input="syncYearlyPrice('pro')">
            </div>
            <div>
              <label class="saas-label">Prix annuel <span class="stg-label-optional">(calculé automatiquement)</span></label>
              <input class="saas-input" type="number" x-model.number="settings.plan_price_pro_yearly" disabled style="background:#F9FAFB;color:#6B7280;">
            </div>
          </div>

          <div class="stg-group-label">Plan Business</div>
          <div class="stg-form-grid">
            <div>
              <label class="saas-label">Prix mensuel</label>
              <input class="saas-input" type="number" min="1" step="500" x-model.number="settings.plan_price_business_monthly" @input="syncYearlyPrice('business')">
            </div>
            <div>
              <label class="saas-label">Prix annuel <span class="stg-label-optional">(calculé automatiquement)</span></label>
              <input class="saas-input" type="number" x-model.number="settings.plan_price_business_yearly" disabled style="background:#F9FAFB;color:#6B7280;">
            </div>
          </div>
          <p class="stg-section-sub" style="margin-top:10px;">Prix annuel = prix mensuel × 12, avec 20 % de remise (cohérent avec le badge « –20 % » affiché sur /tarifs).</p>
        </div>

        <!-- Carte : Primes agents commerciaux -->
        <div class="saas-card" style="padding:24px;margin-bottom:16px;" x-show="settingsSections[settingsCardIndex] === 'bonuses'">
          <div class="stg-group-label">Primes agents commerciaux</div>
          <p class="stg-section-sub" style="margin-bottom:14px;">S'appliquent aux prochains versements, jamais rétroactivement</p>

          <div class="stg-group-label">Forfait par lot de 5 abonnements</div>
          <div class="stg-form-grid" style="margin-bottom:22px;">
            <div>
              <label class="saas-label">Plan Pro</label>
              <input class="saas-input" type="number" min="0" step="500" x-model.number="settings.agent_reward_pro">
            </div>
            <div>
              <label class="saas-label">Plan Business</label>
              <input class="saas-input" type="number" min="0" step="500" x-model.number="settings.agent_reward_business">
            </div>
          </div>

          <div class="stg-group-label">Prime « premier arrivé »</div>
          <div class="stg-form-grid" style="margin-bottom:22px;">
            <div>
              <label class="saas-label">Montant</label>
              <input class="saas-input" type="number" min="0" step="500" x-model.number="settings.bonus_first_to_5_amount">
            </div>
            <div>
              <label class="saas-label">Établissements requis</label>
              <input class="saas-input" type="number" min="1" step="1" x-model.number="settings.bonus_first_to_5_target">
            </div>
          </div>

          <div class="stg-group-label">Autres primes</div>
          <div class="stg-form-grid">
            <div>
              <label class="saas-label">Premier client Business</label>
              <input class="saas-input" type="number" min="0" step="500" x-model.number="settings.bonus_first_business_amount">
            </div>
            <div>
              <label class="saas-label">Top agent du mois</label>
              <input class="saas-input" type="number" min="0" step="500" x-model.number="settings.bonus_monthly_top_amount">
            </div>
            <div>
              <label class="saas-label">Conversion rapide, montant</label>
              <input class="saas-input" type="number" min="0" step="500" x-model.number="settings.bonus_fast_conversion_amount">
            </div>
            <div>
              <label class="saas-label">Conversion rapide, délai (jours)</label>
              <input class="saas-input" type="number" min="1" step="1" x-model.number="settings.bonus_fast_conversion_days">
            </div>
          </div>
        </div>

        <div x-show="settingsError" class="stg-alert stg-alert-error" style="margin-bottom:16px;">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span x-text="settingsError"></span>
        </div>
        <div style="display:flex;justify-content:flex-end;">
          <button class="btn-saas-primary" @click="savePlatformSettings()" :disabled="settingsSaving">
            <span x-show="!settingsSaving">Enregistrer les réglages</span>
            <span x-show="settingsSaving">Enregistrement…</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════
         ONGLET : Sauvegarde
         ════════════════════════════════════════════════════════ -->
    <div x-show="activeTab==='backups'">
      <div class="saas-card" style="padding:20px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
          <div>
            <h2 style="font-size:15px;font-weight:700;color:#111827;margin:0;">Sauvegarde de la base de données</h2>
            <p style="margin:4px 0 0;color:#9CA3AF;font-size:13px;">Dumps quotidiens de la base de données (rétention 7 jours glissants)</p>
          </div>
          <button type="button" class="btn-saas-primary" @click="createBackup()" :disabled="creatingBackup">
            <span x-show="!creatingBackup">+ Sauvegarder maintenant</span>
            <span x-show="creatingBackup" style="display:inline-flex;align-items:center;gap:8px;">
              <div style="width:14px;height:14px;border-radius:50%;border:2px solid rgba(255,255,255,0.3);border-top-color:white;animation:spin 0.7s linear infinite;"></div>
              Sauvegarde en cours…
            </span>
          </button>
        </div>

        <div style="overflow-x:auto;">
          <table class="saas-table" style="width:100%;">
            <thead><tr><th>Date</th><th>Taille</th><th>Fichier</th><th>Actions</th></tr></thead>
            <tbody>
              <template x-for="b in backups" :key="b.filename">
                <tr>
                  <td style="font-weight:600;color:#111827;" x-text="formatDateTime(b.created_at)"></td>
                  <td x-text="formatSize(b.size)"></td>
                  <td style="color:#9CA3AF;font-size:12px;" x-text="b.filename"></td>
                  <td>
                    <button class="btn-saas-secondary" @click="downloadBackup(b)">Télécharger</button>
                  </td>
                </tr>
              </template>
              <template x-if="!loadingBackups && backups.length === 0">
                <tr><td colspan="4" style="text-align:center;padding:40px;color:#9CA3AF;font-size:13px;">Aucune sauvegarde pour l'instant.</td></tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    </div>
  </div>

  <!-- ═══ Modal changement de mot de passe ═══ -->
  <div x-cloak x-show="showPasswordModal" class="saas-modal-bg" @click.self="closePasswordModal()" @keydown.escape.window="closePasswordModal()">
    <div class="saas-modal" style="max-width:420px;" role="dialog" aria-modal="true">
      <div class="saas-modal-header">
        <h2 style="font-size:16px;font-weight:700;margin:0;">Changer le mot de passe</h2>
        <button type="button" class="saas-modal-close" @click="closePasswordModal()"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
      </div>
      <div class="saas-modal-body">
        <div style="display:grid;gap:14px;">
          <div>
            <label class="saas-label">Mot de passe actuel</label>
            <input class="saas-input" type="password" x-model="passwordForm.current" placeholder="••••••••" autocomplete="current-password">
          </div>
          <div>
            <label class="saas-label">Nouveau mot de passe</label>
            <input class="saas-input" type="password" x-model="passwordForm.new" placeholder="Min. 8 caractères, avec une lettre et un chiffre" autocomplete="new-password">
          </div>
          <div>
            <label class="saas-label">Confirmer le nouveau mot de passe</label>
            <input class="saas-input" type="password" x-model="passwordForm.confirm" placeholder="••••••••" autocomplete="new-password">
          </div>
          <div x-show="passwordError" class="stg-alert stg-alert-error">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span x-text="passwordError"></span>
          </div>
        </div>
      </div>
      <div class="saas-modal-footer">
        <button type="button" class="btn-saas-secondary" @click="closePasswordModal()" :disabled="passwordSaving">Annuler</button>
        <button type="button" class="btn-saas-primary" @click="changePassword()" :disabled="passwordSaving">
          <span x-show="!passwordSaving">Enregistrer</span>
          <span x-show="passwordSaving">Enregistrement…</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Toast -->
  <div class="saas-toast" x-show="toast" style="display:grid;">
    <div class="toast-box" :class="toast?.type === 'error' ? 'error' : ''">
      <div style="font-size:14px;font-weight:600;" x-text="toast?.msg"></div>
    </div>
  </div>

</div>
