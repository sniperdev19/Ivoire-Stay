<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php $pageJs = 'admin-settings'; ?>
<?php $pageCss = 'saas-settings'; ?>
<div x-data="adminSettingsPage('<?= $base_url ?>')" x-init="init()">

  <div style="margin-bottom:20px;">
    <h1 style="font-size:22px;font-weight:700;color:#111827;margin:0;">Paramètres</h1>
    <p style="margin:6px 0 0;color:#9CA3AF;">Réglages de la plateforme et de votre compte</p>
  </div>

  <!-- ═══ Mon profil ═══ -->
  <div class="saas-card" style="padding:24px;margin-bottom:24px;">
    <h2 style="font-size:15px;font-weight:700;color:#111827;margin:0 0 16px;">Mon profil</h2>

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

    <div class="stg-panels" style="margin-top:20px;grid-template-columns:1fr;">
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

  <!-- ═══ Réglages plateforme ═══ -->
  <div class="saas-card" style="padding:24px;margin-bottom:24px;">
    <h2 style="font-size:15px;font-weight:700;color:#111827;margin:0 0 4px;">Réglages plateforme</h2>
    <p style="margin:0 0 18px;color:#9CA3AF;font-size:13px;">S'appliquent immédiatement à toute la plateforme</p>

    <div x-show="settingsLoading" style="color:#9CA3AF;font-size:13px;">Chargement…</div>

    <div x-show="!settingsLoading">
      <!-- Agents commerciaux -->
      <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid #F3F4F6;">
        <div>
          <div style="font-size:13px;font-weight:600;color:#111827;">Agents commerciaux</div>
          <div style="font-size:12px;color:#9CA3AF;margin-top:2px;">Inscription, connexion et scan QR — coupe toute la fonctionnalité si désactivé</div>
        </div>
        <label style="position:relative;display:inline-block;width:42px;height:24px;flex-shrink:0;">
          <input type="checkbox" x-model="settings.agents_enabled" style="opacity:0;width:0;height:0;">
          <span @click="settings.agents_enabled = !settings.agents_enabled"
            style="position:absolute;inset:0;border-radius:999px;cursor:pointer;transition:background 0.2s;"
            :style="'background:' + (settings.agents_enabled ? '#6366F1' : '#D1D5DB') + ';'">
            <span style="position:absolute;top:3px;left:3px;width:18px;height:18px;background:#fff;border-radius:50%;transition:transform 0.2s;"
              :style="settings.agents_enabled ? 'transform:translateX(18px);' : ''"></span>
          </span>
        </label>
      </div>

      <!-- Coordonnées de contact -->
      <div style="padding:16px 0;border-bottom:1px solid #F3F4F6;">
        <div style="font-size:13px;font-weight:600;color:#111827;margin-bottom:2px;">Coordonnées de contact du site</div>
        <div style="font-size:12px;color:#9CA3AF;margin-bottom:12px;">Affichées en pied de page et sur /contact</div>
        <div style="display:grid;gap:14px;grid-template-columns:1fr 1fr;">
          <div>
            <label class="saas-label">Email</label>
            <input class="saas-input" type="email" x-model="settings.contact_email" placeholder="contact@afristay.ci">
          </div>
          <div>
            <label class="saas-label">Téléphone</label>
            <input class="saas-input" x-model="settings.contact_phone" placeholder="+225 01 61 95 90 80">
          </div>
        </div>
      </div>

      <!-- Prix des abonnements -->
      <div style="padding:16px 0;border-bottom:1px solid #F3F4F6;">
        <div style="font-size:13px;font-weight:600;color:#111827;margin-bottom:2px;">Prix des abonnements</div>
        <div style="font-size:12px;color:#9CA3AF;margin-bottom:14px;">Montants en FCFA — s'appliquent aux prochains paiements (page /tarifs, checkout, espace hôtelier), jamais aux abonnements déjà payés</div>

        <div style="display:grid;gap:14px;grid-template-columns:1fr 1fr;margin-bottom:14px;">
          <div>
            <label class="saas-label">Pro — mensuel</label>
            <input class="saas-input" type="number" min="1" step="500" x-model.number="settings.plan_price_pro_monthly">
          </div>
          <div>
            <label class="saas-label">Pro — annuel</label>
            <input class="saas-input" type="number" min="1" step="500" x-model.number="settings.plan_price_pro_yearly">
          </div>
        </div>
        <div style="display:grid;gap:14px;grid-template-columns:1fr 1fr;">
          <div>
            <label class="saas-label">Business — mensuel</label>
            <input class="saas-input" type="number" min="1" step="500" x-model.number="settings.plan_price_business_monthly">
          </div>
          <div>
            <label class="saas-label">Business — annuel</label>
            <input class="saas-input" type="number" min="1" step="500" x-model.number="settings.plan_price_business_yearly">
          </div>
        </div>
      </div>

      <!-- Primes agents commerciaux -->
      <div style="padding:16px 0;">
        <div style="font-size:13px;font-weight:600;color:#111827;margin-bottom:2px;">Primes agents commerciaux</div>
        <div style="font-size:12px;color:#9CA3AF;margin-bottom:14px;">Montants en FCFA — s'appliquent aux prochains versements, jamais rétroactivement</div>

        <div style="display:grid;gap:14px;grid-template-columns:1fr 1fr;margin-bottom:14px;">
          <div>
            <label class="saas-label">Forfait tous les 5 abonnements — Pro</label>
            <input class="saas-input" type="number" min="0" step="500" x-model.number="settings.agent_reward_pro">
          </div>
          <div>
            <label class="saas-label">Forfait tous les 5 abonnements — Business</label>
            <input class="saas-input" type="number" min="0" step="500" x-model.number="settings.agent_reward_business">
          </div>
        </div>

        <div style="display:grid;gap:14px;grid-template-columns:1fr 1fr;margin-bottom:14px;">
          <div>
            <label class="saas-label">Prime « premier arrivé » — montant</label>
            <input class="saas-input" type="number" min="0" step="500" x-model.number="settings.bonus_first_to_5_amount">
          </div>
          <div>
            <label class="saas-label">Prime « premier arrivé » — établissements requis</label>
            <input class="saas-input" type="number" min="1" step="1" x-model.number="settings.bonus_first_to_5_target">
          </div>
        </div>

        <div style="display:grid;gap:14px;grid-template-columns:1fr 1fr;margin-bottom:14px;">
          <div>
            <label class="saas-label">Prime « premier client Business »</label>
            <input class="saas-input" type="number" min="0" step="500" x-model.number="settings.bonus_first_business_amount">
          </div>
          <div>
            <label class="saas-label">Prime « top agent du mois »</label>
            <input class="saas-input" type="number" min="0" step="500" x-model.number="settings.bonus_monthly_top_amount">
          </div>
        </div>

        <div style="display:grid;gap:14px;grid-template-columns:1fr 1fr;">
          <div>
            <label class="saas-label">Prime « conversion rapide » — montant</label>
            <input class="saas-input" type="number" min="0" step="500" x-model.number="settings.bonus_fast_conversion_amount">
          </div>
          <div>
            <label class="saas-label">Prime « conversion rapide » — délai (jours)</label>
            <input class="saas-input" type="number" min="1" step="1" x-model.number="settings.bonus_fast_conversion_days">
          </div>
        </div>
      </div>

      <div x-show="settingsError" class="stg-alert stg-alert-error" style="margin-top:14px;">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span x-text="settingsError"></span>
      </div>
      <div style="display:flex;justify-content:flex-end;margin-top:16px;">
        <button class="btn-saas-primary" @click="savePlatformSettings()" :disabled="settingsSaving">
          <span x-show="!settingsSaving">Enregistrer les réglages</span>
          <span x-show="settingsSaving">Enregistrement…</span>
        </button>
      </div>
    </div>
  </div>

  <!-- ═══ Sauvegarde de la base de données ═══ -->
  <div class="saas-card" style="padding:20px;margin-bottom:24px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
      <div>
        <h2 style="font-size:15px;font-weight:700;color:#111827;margin:0;">Sauvegarde de la base de données</h2>
        <p style="margin:4px 0 0;color:#9CA3AF;font-size:13px;">Dumps quotidiens de la base de données — 7 jours glissants</p>
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
