/* ============================================================
   Ivoire Stay — Page SaaS : Paramètres (src/templates/saas/settings.php)
   ============================================================ */

function settingsPage(baseUrl) {
  return {
  establishment:null, loading:true, subscription:null, plans:[], activeTab:'general', saving:false, saveError:null, saveSuccess:false,
  form: { name:'', type:'hotel', address:'', city:'', phone:'', email:'', description:'', website:'' },

  apiHeaders(){ return { 'Content-Type':'application/json', 'Authorization':'Bearer '+localStorage.getItem('token') }; },
  estId(){ return localStorage.getItem('establishment_id')||'1'; },

  async init(){ this.loading=true; try{ const [estRes, subRes, plansRes] = await Promise.all([ fetch(baseUrl + '/api/establishments/'+this.estId(),{headers:this.apiHeaders()}).then(r=>r.json()), fetch(baseUrl + '/api/subscriptions/status?establishment_id='+this.estId(),{headers:this.apiHeaders()}).then(r=>r.json()), fetch(baseUrl + '/api/subscriptions/plans',{headers:this.apiHeaders()}).then(r=>r.json()) ]); if(estRes.success){ this.establishment = estRes.data?.establishment ?? estRes.data; this.form = { name:this.establishment?.name??'', type:this.establishment?.type??'hotel', address:this.establishment?.address??'', city:this.establishment?.city??'', phone:this.establishment?.phone??'', email:this.establishment?.email??'', description:this.establishment?.description??'', website:this.establishment?.website??'' }; } if(subRes.success) this.subscription = subRes.data?.subscription ?? subRes.data; if(plansRes.success) this.plans = plansRes.data?.plans ?? plansRes.data ?? []; }catch(e){ this.form.name='Mon Établissement'; } finally{ this.loading=false; } },

  async saveGeneral(){ this.saving=true; this.saveError=null; this.saveSuccess=false; try{ const res = await fetch(baseUrl + '/api/establishments/'+this.estId(), { method:'PUT', headers:this.apiHeaders(), body:JSON.stringify(this.form) }); const data = await res.json(); if(data.success){ this.saveSuccess=true; setTimeout(()=>this.saveSuccess=false,3000); } else this.saveError = data.message ?? 'Erreur.'; }catch(e){ this.saveError='Erreur réseau.'; } finally{ this.saving=false; } },

  planLabel(p){ return {starter:'Starter',pro:'Pro',business:'Business'}[p]??p; },
  planColor(p){ return {starter:'badge-info',pro:'badge-gold',business:'badge-success'}[p]??'badge'; },
  formatDate(d){ if(!d) return 'Aucune'; return new Date(d).toLocaleDateString('fr-FR',{day:'numeric',month:'long',year:'numeric'}); }
  };
}
