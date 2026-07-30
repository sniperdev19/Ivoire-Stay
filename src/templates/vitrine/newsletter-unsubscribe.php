<?php $base_url = $base_url ?? rtrim(APP_URL, '/'); ?>

<div x-data="newsletterUnsubscribePage('<?= $base_url ?>')" x-init="init()"
  style="max-width:480px;margin:0 auto;padding:96px 24px 120px;text-align:center;font-family:'Inter',sans-serif;">

  <template x-if="loading">
    <p style="color:rgba(27,67,50,0.6);font-size:14px;">Traitement en cours…</p>
  </template>

  <template x-if="!loading">
    <div>
      <h1 style="font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:600;color:#1B4332;margin:0 0 12px;" x-text="success ? 'Désabonnement confirmé' : 'Lien invalide'"></h1>
      <p style="font-size:14px;color:rgba(27,67,50,0.65);line-height:1.6;" x-text="message"></p>
      <a href="<?= $base_url ?>/" style="display:inline-block;margin-top:24px;font-size:13px;color:#C9A84C;font-weight:600;text-decoration:none;">Retour à l'accueil →</a>
    </div>
  </template>

</div>
