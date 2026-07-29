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

  <header class="ag-header">
    <div class="ag-brand">
      <div class="ag-brand-sq">AS</div>
      <span x-text="agent.nom ? ('Bonjour, ' + agent.nom) : 'Espace agent'"></span>
    </div>
    <button class="ag-logout" @click="logout()">Déconnexion</button>
  </header>

  <nav class="ag-tabs">
    <a href="<?= $base_url ?>/agent/dashboard" class="ag-tab">Tableau de bord</a>
    <a href="<?= $base_url ?>/agent/profile" class="ag-tab active">Mon profil</a>
  </nav>

  <div class="ag-container">

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

</body>
</html>
