<?php
// Fournir un fallback pour $base_url si non injecté
$base_url = $base_url ?? rtrim(APP_URL, '/');
?>
<!-- Paramètres — template injecté via $content -->

<div x-data="{
  establishment:null, loading:true, subscription:null, plans:[], activeTab:'general', saving:false, saveError:null, saveSuccess:false,
  form: { name:'', type:'hotel', address:'', city:'', phone:'', email:'', description:'', website:'' },

  // Photo de couverture
  photoPreview: null,
  photoFile: null,
  photoUploading: false,
  photoSuccess: false,
  photoError: null,

  async uploadPhoto() {
    if (!this.photoFile) return;
    this.photoUploading = true;
    this.photoSuccess = false;
    this.photoError = null;
    try {
      const estId = this.estId();
      const formData = new FormData();
      formData.append('photo', this.photoFile);
      const res = await fetch(
        '<?= $base_url ?>/api/establishments/' + estId + '/photo',
        {
          method: 'POST',
          headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') },
          body: formData
        }
      );
      const data = await res.json();
      if (data.success) {
        this.photoSuccess = true;
        setTimeout(() => this.photoSuccess = false, 3000);
      } else {
        this.photoError = data.message ?? 'Erreur upload photo.';
      }
    } catch(e) {
      this.photoError = 'Erreur réseau.';
    } finally {
      this.photoUploading = false;
    }
  },

  handlePhotoFile(e) {
    const file = e.target.files[0];
    if (!file) return;
    this.photoFile = file;
    const reader = new FileReader();
    reader.onload = (ev) => { this.photoPreview = ev.target.result; };
    reader.readAsDataURL(file);
  },

  apiHeaders(){ const token = localStorage.getItem('token') ?? ''; return { 'Content-Type':'application/json', 'Authorization':'Bearer ' + token }; },
  estId(){
    let id = localStorage.getItem('establishment_id');
    if (id && id !== 'null' && id !== 'undefined') return id;
    try { const list = JSON.parse(localStorage.getItem('establishments') || '[]'); if (Array.isArray(list) && list.length > 0) { id = list[0].id ?? list[0].establishment_id; if (id) { localStorage.setItem('establishment_id', String(id)); return String(id); } } } catch(e) {}
    try { const user = JSON.parse(localStorage.getItem('user') || '{}'); if (user.establishment_id) { localStorage.setItem('establishment_id', String(user.establishment_id)); return String(user.establishment_id); } } catch(e) {}
    return '1';
  },

  async init(){ this.loading=true; try{ const [estRes, subRes, plansRes] = await Promise.all([ fetch('<?= $base_url ?>/api/establishments/'+this.estId(),{headers:this.apiHeaders()}).then(r=>r.json()), fetch('<?= $base_url ?>/api/subscriptions/status?establishment_id='+this.estId(),{headers:this.apiHeaders()}).then(r=>r.json()), fetch('<?= $base_url ?>/api/subscriptions/plans',{headers:this.apiHeaders()}).then(r=>r.json()) ]); if(estRes.success){ this.establishment = estRes.data?.establishment ?? estRes.data; this.form = { name:this.establishment?.name??'', type:this.establishment?.type??'hotel', address:this.establishment?.address??'', city:this.establishment?.city??'', phone:this.establishment?.phone??'', email:this.establishment?.email??'', description:this.establishment?.description??'', website:this.establishment?.website??'' }; } if(subRes.success) this.subscription = subRes.data?.subscription ?? subRes.data; if(plansRes.success) this.plans = plansRes.data?.plans ?? plansRes.data ?? []; }catch(e){ this.form.name='Mon Établissement'; } finally{ this.loading=false; } },

  async saveGeneral(){ this.saving=true; this.saveError=null; this.saveSuccess=false; try{ const res = await fetch('<?= $base_url ?>/api/establishments/'+this.estId(), { method:'PUT', headers:this.apiHeaders(), body:JSON.stringify(this.form) }); const data = await res.json(); if(data.success){ this.saveSuccess=true; setTimeout(()=>this.saveSuccess=false,3000); } else this.saveError = data.message ?? 'Erreur.'; }catch(e){ this.saveError='Erreur réseau.'; } finally{ this.saving=false; } },

  planLabel(p){ return {starter:'Starter',pro:'Pro',business:'Business'}[p]??p; },
  planColor(p){ return {starter:'badge-info',pro:'badge-gold',business:'badge-success'}[p]??'badge'; },
  formatDate(d){ if(!d) return 'Aucune'; return new Date(d).toLocaleDateString('fr-FR',{day:'numeric',month:'long',year:'numeric'}); }
}"
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
    <div class="saas-card" style="margin-bottom:20px;padding:0;overflow:hidden;">
      <div style="padding:20px 24px 16px;border-bottom:1px solid rgba(0,0,0,0.05);">
        <h3 style="font-size:15px;font-weight:700;color:#111827;margin:0 0 4px;">
          Photo de couverture
        </h3>
        <p style="font-size:12px;color:#9CA3AF;margin:0;">
          Cette photo apparaît sur la vitrine publique et dans les résultats de recherche.
        </p>
      </div>

      <div style="padding:24px;display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;">

        <!-- Aperçu -->
        <div style="
          width:280px;height:160px;border-radius:12px;
          overflow:hidden;background:#F3F4F6;
          border:2px dashed rgba(0,0,0,0.1);
          flex-shrink:0;position:relative;
        ">
          <!-- Image actuelle ou preview -->
          <img
            x-show="photoPreview"
            :src="photoPreview"
            style="width:100%;height:100%;object-fit:cover;display:block;">
          <img
            x-show="!photoPreview && establishment?.cover_photo"
            :src="'<?= $base_url ?>/' + (establishment?.cover_photo ?? '').replace(/^\/+/, '')"
            style="width:100%;height:100%;object-fit:cover;display:block;"
            @error="$event.target.style.display='none'">
          <!-- Placeholder si pas de photo -->
          <div
            x-show="!photoPreview && !establishment?.cover_photo"
            style="width:100%;height:100%;display:flex;flex-direction:column;
              align-items:center;justify-content:center;gap:8px;">
            <svg xmlns="http://www.w3.org/2000/svg"
              style="width:32px;height:32px;color:#D1D5DB;" fill="none"
              viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2
                0 012.828 0L20 20M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0
                00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span style="font-size:11px;color:#9CA3AF;">Aucune photo</span>
          </div>
        </div>

        <!-- Actions -->
        <div style="flex:1;min-width:200px;display:flex;flex-direction:column;gap:12px;">

          <div style="font-size:12px;color:#6B7280;line-height:1.6;">
            Formats acceptés : <strong>JPG, PNG, WebP</strong><br>
            Taille maximale : <strong>5 Mo</strong><br>
            Dimensions recommandées : <strong>1200 × 600 px</strong>
          </div>

          <!-- Input file caché -->
          <input
            type="file"
            accept="image/jpeg,image/png,image/webp"
            id="coverPhotoInput"
            style="display:none;"
            @change="handlePhotoFile($event)">

          <!-- Bouton choisir -->
          <button
            class="btn-saas-secondary"
            type="button"
            @click="document.getElementById('coverPhotoInput').click()"
            style="width:fit-content;">
            <svg xmlns="http://www.w3.org/2000/svg"
              style="width:14px;height:14px;" fill="none"
              viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0
                0L8 8m4-4v12"/>
            </svg>
            Choisir une photo
          </button>

          <!-- Nom fichier sélectionné -->
          <div x-show="photoFile"
            style="font-size:12px;color:#374151;display:flex;
              align-items:center;gap:6px;">
            <svg xmlns="http://www.w3.org/2000/svg"
              style="width:13px;height:13px;color:#C9A84C;" fill="none"
              viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4
                4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
            </svg>
            <span x-text="photoFile?.name"></span>
          </div>

          <!-- Bouton upload -->
          <button
            class="btn-saas-primary"
            type="button"
            x-show="photoFile"
            @click="uploadPhoto()"
            :disabled="photoUploading"
            style="width:fit-content;">
            <div x-show="photoUploading"
              style="width:14px;height:14px;border-radius:50%;
                border:2px solid rgba(255,255,255,0.3);
                border-top-color:white;
                animation:spin 0.7s linear infinite;">
            </div>
            <span x-show="!photoUploading">Enregistrer la photo</span>
            <span x-show="photoUploading">Envoi en cours…</span>
          </button>

          <!-- Succès -->
          <div x-show="photoSuccess"
            style="display:flex;align-items:center;gap:8px;
              font-size:13px;color:#16a34a;font-weight:500;">
            <svg xmlns="http://www.w3.org/2000/svg"
              style="width:15px;height:15px;" fill="none"
              viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round"
                stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
            Photo mise à jour avec succès !
          </div>

          <!-- Erreur -->
          <div x-show="photoError"
            style="font-size:12px;color:#DC2626;"
            x-text="photoError">
          </div>
        </div>
      </div>
    </div>

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
