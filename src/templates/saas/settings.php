<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php ?>
<!-- Paramètres — template injecté via $content -->

<?php $pageJs = 'saas-settings'; ?>
<div x-data="settingsPage('<?= $base_url ?>')"
 x-init="init()" @keydown.escape.window="activeTab='general'">

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
    <div><h1 style="margin:0;font-size:20px;font-weight:700;color:#111827;">Paramètres</h1><p style="margin:6px 0 0;color:#9CA3AF;">Gérez les informations de votre établissement et abonnement</p></div>
  </div>

  <!-- Tabs -->
  <div style="display:flex;gap:8px;margin-bottom:12px;">
    <button @click="activeTab='general'" :class="activeTab==='general'? 'btn-saas-primary':''" class="btn-saas-secondary">Général</button>
    <button @click="activeTab='subscription'" :class="activeTab==='subscription'? 'btn-saas-primary':''" class="btn-saas-secondary">Abonnement</button>
    <button @click="activeTab='account'" :class="activeTab==='account'? 'btn-saas-primary':''" class="btn-saas-secondary">Compte</button>
  </div>

  <!-- Général -->
  <div x-show="activeTab==='general'">
    <div class="saas-card" style="padding:24px;">
      <h3>Informations de l'établissement</h3>
      <p style="color:#6B7280;margin-top:6px;">Ces informations apparaissent sur votre vitrine publique</p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:12px;">
        <div style="grid-column:span 2;"><label class="saas-label">Nom de l'établissement</label><input class="saas-input" x-model="form.name" /></div>
        <div><label class="saas-label">Type</label><select class="saas-input" x-model="form.type"><option value="hotel">Hôtel</option><option value="residence">Résidence</option><option value="villa">Villa</option></select></div>
        <div><label class="saas-label">Ville</label><input class="saas-input" x-model="form.city" /></div>
        <div><label class="saas-label">Adresse</label><input class="saas-input" x-model="form.address" /></div>
        <div><label class="saas-label">Téléphone</label><input class="saas-input" x-model="form.phone" /></div>
        <div><label class="saas-label">Email</label><input class="saas-input" x-model="form.email" /></div>
        <div style="grid-column:span 2;"><label class="saas-label">Description</label><textarea class="saas-input" rows="4" x-model="form.description"></textarea></div>
        <div><label class="saas-label">Site web</label><input class="saas-input" x-model="form.website" /></div>
      </div>

      <div style="margin-top:12px;">
        <template x-if="saveSuccess"><div style="background:rgba(16,185,129,0.06);padding:10px;border-radius:8px;color:#065f46;">Modifications enregistrées !</div></template>
        <template x-if="saveError"><div style="background:rgba(220,38,38,0.06);padding:10px;border-radius:8px;color:#DC2626;" x-text="saveError"></div></template>
      </div>

      <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end;"><button class="btn-saas-primary" @click="saveGeneral()" :disabled="saving"><span x-show="!saving">Enregistrer les modifications</span><div x-show="saving" style="width:14px;height:14px;border-radius:50%;border:2px solid rgba(255,255,255,0.3);border-top-color:white;animation:spin 0.7s linear infinite;"></div></button></div>
    </div>
  </div>

  <!-- Abonnement -->
  <div x-show="activeTab==='subscription'">
    <div class="saas-card" style="padding:28px;background:#0F2B20;color:white;border-radius:20px;margin-bottom:12px;">
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <div>
          <div style="font-size:12px;color:rgba(255,255,255,0.7);">Plan actuel</div>
          <div style="font-family:Cormorant,serif;font-size:28px;color:#C9A84C;font-weight:700;" x-text="planLabel(subscription?.plan)"></div>
          <div style="margin-top:6px;color:rgba(255,255,255,0.8);">Valide jusqu'au <span x-text="formatDate(subscription?.expires_at)"></span></div>
        </div>
        <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-end;"><div style="display:flex;gap:8px;"><div style="font-weight:700;color:white;">Chambres</div><div style="background:rgba(255,255,255,0.06);padding:6px 10px;border-radius:8px;" x-text="establishment?.rooms_count ?? 0"></div></div><a href="/tarifs" target="_blank" class="btn-saas-primary">Gérer mon abonnement →</a></div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
      <template x-for="p in ['starter','pro','business']" :key="p">
        <div class="saas-card" style="padding:24px;border:1px solid transparent;" :style="subscription?.plan===p ? 'border-color:#C9A84C;' : '' ">
          <div style="display:flex;justify-content:space-between;align-items:center;"><div style="font-size:18px;font-weight:700;color:#134E4A;" x-text="planLabel(p)"></div><div x-show="subscription?.plan===p" class="badge badge-success">Plan actuel</div></div>
          <div style="font-family:Cormorant,serif;font-size:28px;color:#C9A84C;margin-top:8px;"> <span x-text="p==='starter'? 'Gratuit' : (p==='pro'? '9 000 FCFA/mois':'20 000 FCFA/mois')"></span></div>
          <ul style="margin-top:10px;color:#6B7280;"><li>Fonctionnalité A</li><li>Fonctionnalité B</li><li>Fonctionnalité C</li></ul>
          <div style="margin-top:12px;">
            <template x-if="subscription?.plan!==p"><button class="btn-saas-primary" @click="window.location='/tarifs'">Choisir ce plan</button></template>
          </div>
        </div>
      </template>
    </div>
  </div>

  <!-- Compte -->
  <div x-show="activeTab==='account'">
    <div class="saas-card" style="padding:16px;">
      <h3>Informations du compte</h3>
      <div style="display:flex;gap:16px;align-items:center;margin-top:12px;"><div style="width:56px;height:56px;border-radius:12px;display:grid;place-items:center;background:#C9A84C;color:white;font-weight:800;font-size:18px;"> <span x-text="(JSON.parse(localStorage.getItem('user')||'{}').name||'U').split(' ').map(s=>s[0]).slice(0,2).join('')"></span></div>
        <div><div style="font-weight:700;" x-text="JSON.parse(localStorage.getItem('user')||'{}').name"></div><div style="color:#9CA3AF;" x-text="JSON.parse(localStorage.getItem('user')||'{}').email"></div><div style="margin-top:6px;" ><span class="badge" :class="planColor(JSON.parse(localStorage.getItem('user')||'{}').role)"> <span x-text="JSON.parse(localStorage.getItem('user')||'{}').role"></span></span></div></div>
      </div>

      <div style="margin-top:16px;display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div style="background:#FEF3C7;padding:14px;border-radius:12px;border:1px solid rgba(217,119,6,0.12);"><svg xmlns="http://www.w3.org/2000/svg" style="width:24px;height:24px;color:#C9A84C;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0-1.657 0-3 0-3"/></svg><div style="font-weight:700;margin-top:8px;">Sécurité</div><div style="color:#92400E;margin-top:6px;">La modification du mot de passe sera disponible dans une prochaine mise à jour.</div><div style="margin-top:8px;"><button class="btn-saas-secondary" disabled>Changer le mot de passe</button></div></div>

        <div style="background:rgba(220,38,38,0.06);padding:14px;border-radius:12px;border:1px solid rgba(220,38,38,0.12);"><div style="font-weight:700;color:#DC2626;">Zone de danger</div><div style="margin-top:8px;color:#6B7280;">Supprimer mon compte et toutes les données associées</div><div style="margin-top:8px;"><button class="btn-saas-danger" disabled>Contacter le support pour supprimer le compte</button></div></div>
      </div>
    </div>
  </div>

</div>
<?php ?>
<div class="settings">
    <h2>Paramètres</h2>
    <div id="settings">Configuration de l'établissement...</div>
</div>
