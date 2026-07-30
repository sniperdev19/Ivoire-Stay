<?php /** @var string $base_url */ if (!isset($base_url)) $base_url = rtrim(APP_URL, "/"); ?>
<?php $pageCss = 'saas-help'; ?>

<script>
const _faqItems = <?= json_encode([
  ["q" => "Comment créer un compte ?",              "a" => "Cliquez sur « S'inscrire » depuis la page d'accueil, remplissez le formulaire, puis installez l'application pour accéder à votre espace."],
  ["q" => "Comment ajouter une propriété ?",        "a" => "Rendez-vous dans Paramètres, onglet Général, et renseignez les informations de votre établissement."],
  ["q" => "Comment gérer les réservations ?",       "a" => "La page Planning affiche toutes les réservations par chambre sous forme de calendrier. Cliquez sur un créneau pour voir le détail."],
  ["q" => "Comment exporter des factures ?",        "a" => "Dans la page Factures, ouvrez le détail d'une facture puis utilisez le bouton PDF pour la télécharger."],
  ["q" => "Quelle est la politique d'annulation ?", "a" => "La politique d'annulation est définie par chaque établissement et affichée sur sa fiche publique."],
  ["q" => "Comment changer d'abonnement ?",         "a" => "Allez dans Paramètres → Abonnement pour voir les plans disponibles et changer vers un plan supérieur."],
  ["q" => "Comment supprimer une réservation ?",    "a" => "Ouvrez le détail d'une réservation depuis la page Réservations, puis cliquez sur Annuler ou Supprimer."],
  ["q" => "Comment contacter le support ?",         "a" => "Utilisez le formulaire de contact dans cette page ou écrivez directement à support@afristay.ci."],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;
</script>

<div x-data="{ faqItems: _faqItems, openFaq: null, faqSearch: '' }">

  <!-- En-tête -->
  <div class="hlp-header">
    <h1 class="hlp-title">Aide &amp; FAQ</h1>
    <p class="hlp-sub">Guides rapides, questions fréquentes et contact support</p>
  </div>

  <!-- Recherche FAQ -->
  <div class="hlp-search-wrap">
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
    <input class="hlp-search" x-model="faqSearch" type="text" placeholder="Rechercher dans l'aide…">
  </div>

  <!-- Corps 2 colonnes -->
  <div class="hlp-layout">

    <!-- Colonne principale -->
    <div>

      <!-- Démarrage rapide -->
      <div class="saas-card" style="padding:22px;margin-bottom:14px;">
        <div class="hlp-section-head">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          <h3>Démarrage rapide</h3>
        </div>
        <div class="hlp-steps">
          <div class="hlp-step">
            <div class="hlp-step-icon hlp-step-icon-blue">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
            </div>
            <div class="hlp-step-title">1. Créer votre établissement</div>
            <div class="hlp-step-desc">Renseignez le nom, le type, la ville et les coordonnées dans les Paramètres.</div>
          </div>
          <div class="hlp-step">
            <div class="hlp-step-icon hlp-step-icon-green">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            </div>
            <div class="hlp-step-title">2. Ajouter vos chambres</div>
            <div class="hlp-step-desc">Définissez les types, capacités et tarifs depuis la page Chambres.</div>
          </div>
          <div class="hlp-step">
            <div class="hlp-step-icon hlp-step-icon-gold">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div class="hlp-step-title">3. Gérer les réservations</div>
            <div class="hlp-step-desc">Activez votre calendrier et acceptez les premières réservations depuis le Planning.</div>
          </div>
        </div>
      </div>

      <!-- FAQ accordéon -->
      <div class="saas-card" style="padding:22px;">
        <div class="hlp-section-head">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <h3>Questions fréquentes</h3>
        </div>
        <div class="hlp-faq-list">
          <template x-for="(f, idx) in faqItems.filter(f => !faqSearch || f.q.toLowerCase().includes(faqSearch.toLowerCase()) || f.a.toLowerCase().includes(faqSearch.toLowerCase()))" :key="idx">
            <div class="hlp-faq-item">
              <button class="hlp-faq-btn" @click="openFaq === idx ? openFaq = null : openFaq = idx">
                <span class="hlp-faq-q" x-text="f.q"></span>
                <svg class="hlp-faq-arrow" :class="openFaq === idx ? 'open' : ''"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
              </button>
              <div x-show="openFaq === idx" x-transition class="hlp-faq-answer" x-text="f.a"></div>
            </div>
          </template>
          <div x-show="faqItems.filter(f => !faqSearch || f.q.toLowerCase().includes(faqSearch.toLowerCase()) || f.a.toLowerCase().includes(faqSearch.toLowerCase())).length === 0"
               style="padding:20px 0;text-align:center;color:#9CA3AF;font-size:14px;">
            Aucun résultat pour « <span x-text="faqSearch"></span> »
          </div>
        </div>
      </div>

    </div>

    <!-- Sidebar -->
    <aside>

      <!-- Contact support -->
      <div class="hlp-contact-card">
        <div class="hlp-contact-label">Support</div>
        <div class="hlp-contact-title">Une question ? On est là.</div>
        <div class="hlp-contact-row">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          support@afristay.ci
        </div>
        <div class="hlp-contact-row">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
          +225 07 00 00 00 00
        </div>
        <a href="mailto:support@afristay.ci" class="hlp-contact-btn">Envoyer un message</a>
      </div>

      <!-- Documentation -->
      <div class="hlp-docs-card">
        <div class="hlp-docs-icon">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
        </div>
        <div class="hlp-docs-title">Documentation</div>
        <div class="hlp-docs-desc">Fonctionnalités de la plateforme, formules d'abonnement et permissions par rôle.</div>
        <a href="<?= $base_url ?>/docs" class="btn-saas-secondary" style="width:100%;justify-content:center;">Voir la documentation</a>
      </div>

      <!-- Liens rapides -->
      <div class="hlp-links-card">
        <div class="hlp-section-head" style="margin-bottom:8px;">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <h3>Liens rapides</h3>
        </div>
        <a href="<?= $base_url ?>/saas/rooms" class="hlp-link-row">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
          Gérer les chambres
          <svg class="hlp-link-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>
        <a href="<?= $base_url ?>/saas/bookings" class="hlp-link-row">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          Voir les réservations
          <svg class="hlp-link-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>
        <a href="<?= $base_url ?>/saas/settings" class="hlp-link-row">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
          Paramètres
          <svg class="hlp-link-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>
        <a href="<?= $base_url ?>/tarifs" class="hlp-link-row">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
          Plans & tarifs
          <svg class="hlp-link-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>
      </div>

    </aside>
  </div>

</div>
