<?php
// Fournir un fallback pour $base_url si non injecté
$base_url = $base_url ?? rtrim(APP_URL, '/');
?>
<!-- Aide & FAQ — template injecté via $content -->

<div x-data="{ openFaq:null, activeSection:'start' }">

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
    <div><h1 style="margin:0;font-size:20px;font-weight:700;color:#111827;">Aide &amp; FAQ</h1><p style="margin:6px 0 0;color:#9CA3AF;">Guides rapides, questions fréquentes et contact support</p></div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 320px;gap:16px;">
    <div>
      <div class="saas-card" style="padding:18px;margin-bottom:12px;"><h3>Démarrage rapide</h3>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:12px;">
          <div style="padding:12px;border-radius:12px;background:#F8FAFF;"><div style="font-weight:700;color:#111827;">Créer une propriété</div><div style="color:#6B7280;margin-top:6px;">Ajoutez les informations de base et les photos.</div></div>
          <div style="padding:12px;border-radius:12px;background:#F8FAFF;"><div style="font-weight:700;color:#111827;">Ajouter des chambres</div><div style="color:#6B7280;margin-top:6px;">Définissez types, capacités et tarifs.</div></div>
          <div style="padding:12px;border-radius:12px;background:#F8FAFF;"><div style="font-weight:700;color:#111827;">Accepter les réservations</div><div style="color:#6B7280;margin-top:6px;">Activez la disponibilité et le calendrier.</div></div>
        </div>
      </div>

      <div class="saas-card" style="padding:18px;">
        <h3>FAQ</h3>
        <div style="margin-top:12px;">
          <template x-for="(f,idx) in [
            {q:'Comment créer un compte ? ', a:'Cliquez sur Register puis remplissez le formulaire.'},
            {q:'Comment ajouter une propriété ?', a:'Allez dans Paramètres → Général et cliquez sur Ajouter.'},
            {q:'Comment gérer les réservations ?', a:'La page Planning affiche toutes les réservations par chambre.'},
            {q:'Comment exporter des factures ?', a:'Dans Factures, utilisez le bouton PDF pour télécharger.'},
            {q:'Quelle est la politique d'annulation ?', a:'La politique est définie par l’établissement et affichée sur la fiche.'},
            {q:'Comment contacter le support ?', a:'Utilisez le formulaire en bas ou écrivez à support@example.com.'},
            {q:'Comment changer d’abonnement ?', a:'Allez dans Paramètres → Abonnement pour changer de plan.'},
            {q:'Comment supprimer une réservation ?', a:'Ouvrez le détail de la réservation puis cliquez sur Supprimer.'}
          ]" :key="idx">
            <div style="border-bottom:1px solid rgba(0,0,0,0.04);padding:10px 0;">
              <button @click="openFaq===idx?openFaq=null:openFaq=idx" style="width:100%;display:flex;justify-content:space-between;align-items:center;background:transparent;border:0;padding:8px 0;">
                <div style="text-align:left;"><div style="font-weight:700;color:#111827;" x-text="f.q"></div></div>
                <svg :class="openFaq===idx? 'rotate-180':''" xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;color:#9CA3AF;transition:transform .2s;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </button>
              <div x-show="openFaq===idx" x-transition style="color:#6B7280;margin-top:8px;" x-text="f.a"></div>
            </div>
          </template>
        </div>
      </div>
    </div>

    <aside>
      <div class="saas-card" style="padding:16px;margin-bottom:12px;"><h4>Contact support</h4><div style="color:#6B7280;margin-top:8px;">support@example.com<br/>+225 00 00 00 00</div><div style="margin-top:10px;"><a href="mailto:support@example.com" class="btn-saas-primary">Envoyer un message</a></div></div>
      <div class="saas-card" style="padding:16px;"><h4>Documentation</h4><div style="color:#6B7280;margin-top:8px;">Consultez la documentation pour les guides complets.</div><div style="margin-top:10px;"><a href="<?= $base_url ?>/docs" class="btn-saas-secondary">Voir la documentation</a></div></div>
    </aside>
  </div>

</div>
<?php ?>
<div class="help">
    <h2>Aide</h2>
    <p>Documentation et support.</p>
</div>
