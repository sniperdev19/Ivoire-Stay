# Mémoire du projet — Afristay (Ivoire Stay)

> **Instructions pour Claude** : ce fichier remplace un scan complet du repo en
> début de tâche. Le lire d'abord. Après toute modification structurante
> (nouvelle table, nouveau contrôleur/service, nouvelle route, changement de
> convention, décision produit), **mettre à jour la section concernée ici
> même**, en particulier le Journal des évolutions en bas de fichier. Rester
> concis : ce fichier documente la structure et les décisions, pas le détail
> du code (qui reste lisible directement dans les fichiers).

## Vue d'ensemble

SaaS B2B de gestion hôtelière pour le marché ivoirien + vitrine B2C publique.
PHP 8 vanilla (pas de framework), MySQL, Alpine.js + Tailwind côté front,
PWA installable. Composer autoload PSR-4 : `Core\`, `Controllers\`, `Models\`,
`Services\` → tous dans `src/`.

Dépendances clés (`composer.json`) : PHPMailer, FPDF (factures PDF),
minishlink/web-push (notifications push), web-auth/webauthn-lib (passkeys).

## ⚠️ Duplication src/ ↔ copy/

**Le répertoire `copy/` est un miroir de `src/`, `public/`, `config/` et
`scripts/`, tenu manuellement à jour pour représenter ce qui doit être
déployé en production.**

**Règle impérative : toute modification dans `src/`, `public/`, `config/` ou
`scripts/` doit être répliquée à l'identique dans `copy/`**, sauf si
explicitement dit le contraire. Avant de considérer une tâche terminée,
vérifier que les deux arborescences sont resynchronisées (`diff` rapide si
doute). `copy/scripts/schema.sql` et `copy/scripts/wipe-db.sql` ont déjà
dérivé une fois par le passé (remis à niveau le 2026-07-20) — rester
vigilant sur ces fichiers de référence en particulier, faciles à oublier
car non exécutés automatiquement.

**Depuis le 2026-07-30, `copy/` est un `git worktree` de la branche `main`**
(pas un simple dossier ignoré) : `git worktree list` le montre pointé sur
`main`. Committer/pousser depuis `copy/` (`git -C copy add/commit/push`)
revient à committer directement sur `main`, que l'hébergeur ira ensuite lier
en pull/déploiement. `copy/.gitignore` (propre à la branche `main`, distinct
du `.gitignore` racine de `developpement`) exclut `.env`, `/storage/` et
`/public/assets/uploads/*` — `vendor/` reste committé sur `main` puisque
l'hébergeur n'a pas d'accès shell/composer. `.env` doit être déposé
manuellement sur l'hébergeur (jamais versionné). Après resynchronisation de
`copy/`, penser à committer+pousser sur `main` en plus de resynchroniser les
fichiers (les deux étapes sont désormais distinctes).

## Rôles & espaces applicatifs

- **`superadmin`** — administre la plateforme (`/admin/*`), gère
  établissements, propriétaires, retraits.
- **`owner`** — s'inscrit via `/register` (auto-inscription), gère un ou
  plusieurs établissements (`/saas/*` selon plan).
- **`receptionist`** — créé PAR un owner via `TeamController` (pas
  d'auto-inscription), accès `/saas/*` restreint (pas de Finance/Paramètres).
- **`client`** — voyageur, pas de compte requis pour réserver (public_clients
  + guest_token opaque pour s'abonner aux notifications push de sa résa).
- **`agent`** — agent commercial démarchant des établissements
  (fonctionnalité temporaire, cf. journal 2026-07-27). **Pas un rôle
  `users`** : entité séparée (table `agents`), s'inscrit via
  `/agent/register` (nom, numéro, opérateur_money, password), connexion par
  numéro sur `/agent/login`, tableau de bord `/agent/dashboard`.

## Modèle de données (voir `scripts/schema.sql`, généré/consolidé)

Tables cœur métier : `users`, `establishments`, `room_types`, `rooms`,
`bookings`, `public_clients`, `invoices`, `payments`, `expenses`,
`subscriptions`, `payout_requests`.

Tables médias : `room_photos`, `establishment_photos`.

Tables notifications/push : `notifications`, `push_subscriptions`,
`guest_push_subscriptions` (voyageurs sans compte, protégé par
`bookings.guest_token`).

Tables sécurité/auth : `rate_limits`, `token_blacklist` (révocation JWT),
`device_attestations`, `webauthn_challenges`, `webauthn_credentials`
(passkeys — gate résiduel, voir plus bas), `password_resets`,
`email_verifications` (ajoutée 2026-07-20).

Autre : `scheduled_job_runs` (jobs "cron" déclenchés par requête HTTP, pas de
vrai cron — voir `Services\SchedulerService`).

Tables agents commerciaux (temporaire, cf. `AGENTS_ENABLED`) : `agents`,
`agent_establishments` (lien agent↔établissement, premier scan gagne),
`agent_referrals` (comptage premiers-abonnements par plan), `agent_payouts`
(versements forfaitaires par palier de 10) ; `establishments.qr_token`.

`scripts/migration_*.sql` = historique incrémental ; `scripts/schema.sql` =
snapshot consolidé pour init base vierge ; `scripts/wipe-db.sql` = vidage
complet structure conservée.

## Architecture technique (`src/Core/`)

- **Router.php** — routes déclarées dans `config/routes.php`, middlewares
  passés en tableau (`['auth']`, `['auth', 'role:owner|superadmin']`).
- **Request / Response** — `Response::render($template, $data)` choisit le
  layout wrapper selon le préfixe (`saas/`→`saas/layout.php`,
  `vitrine/`→`vitrine/layout.php`, `admin/`→`admin/layout.php`), **sauf**
  liste blanche de pages autonomes (login, register, install,
  forgot-password, reset-password, verify-email) qui rendent leur propre
  `<html>` complet sans wrapper.
- **Guard.php** — garde-fou anti-IDOR central : `requireEstablishment()`,
  `requireBooking()`, `requireRoom()`, etc. **Tout contrôleur qui prend un
  `{id}` en paramètre de route doit passer par un `Guard::require*()`** —
  2 failles IDOR déjà trouvées et corrigées par le passé (subscriptions,
  reports) faute de ça.
- **PlanGate.php** — vérifie les limites de plan (`config/plans.php` :
  starter/pro/business, max_rooms, max_establishments, features).
- **Middleware.php** — `auth` (JWT), `role:x|y`.
- **RateLimiter.php** — throttling par clé (ip+action), table `rate_limits`.
- **Slug.php** — génération de slugs (translitération accents explicite,
  pas d'iconv//TRANSLIT car buggé sous Windows) ; `Slug::withId()` = base +
  `-{id}` pour garantir l'unicité.
- **BaseModel** — CRUD générique (`find`, `where`, `create`, `update`,
  `delete`), pas d'ORM, pas de fillable (attention en confiant des données
  utilisateur brutes à `update()`).

## Authentification — points sensibles

- JWT (`AuthService`), révocation via `token_blacklist`.
- **Connexion SaaS réservée à l'app installée (PWA standalone)** —
  `saas.js::saasLayout.init()` redirige vers `/login` si pas en mode
  standalone. Un gate WebAuthn/passkey existait pour prouver
  cryptographiquement l'installation ; **retiré le 2026-07-17** (trop de
  friction), le gate restant est côté client uniquement. Les tables
  webauthn restent utilisées par le flux de setup.
- **Vérification d'email** (ajoutée 2026-07-20) : envoyée à l'inscription
  (`/register` uniquement, pas les comptes équipe), lien 24h, **ne bloque
  jamais la connexion** — bandeau non bloquant dans `saas/layout.php`
  (`showEmailVerifyBanner`, owner non vérifié uniquement) avec renvoi
  possible. Cohérent avec le retrait du gate WebAuthn : friction au setup,
  jamais à l'usage.
- Établissements en excédent après downgrade de plan → gelés en 2 phases
  (`EstablishmentFreezeService`), badge "GELÉ" visible en prod, les plus
  anciens (dans la limite du nouveau plan) restent actifs.

## Front-end

- **Vitrine publique** (`src/templates/vitrine/`) — layout commun avec
  navbar flottante (`vitrineNav()` dans `public/assets/js/vitrine.js`),
  toujours en mode "pilule" (scrolled=true dès le chargement, plus de
  transparence initiale sur `/`, changé le 2026-07-19). Images statiques
  dans `public/assets/img/vitrine/` (auto-hébergées, pas de CDN externe —
  CSP stricte `img-src 'self' data: blob:`).
- **SaaS** (`src/templates/saas/`) — `saasLayout()` dans `public/assets/js/saas.js`
  gère user/establishment/establishments en cache localStorage + refresh
  serveur. Pages auth (login/register/forgot-password/reset-password/
  install/verify-email) : gabarit `.lg-*`/`.rg-*` deux panneaux, panneau
  gauche masqué ≤1024px (le lien retour doit alors être dupliqué en
  `.lg-mobile-back`/`.rg-mobile-back` visible sur mobile — oubli fréquent
  pour toute nouvelle page de ce type).
- **Admin plateforme** (`src/templates/admin/`) — réservé superadmin, garde
  de rôle côté JS (pas de garde serveur sur les pages HTML elles-mêmes,
  seulement sur les endpoints API `/api/admin/*`).
- `anti-inspect.js` — dissuasion basique (clic droit, sélection, DevTools)
  sur la vitrine publique, pas une vraie protection.

## Emails (`Services\MailService`)

Un layout HTML commun (`self::layout()`) + helpers (`btn`, `infoTable`).
Méthodes existantes : `welcome`, `passwordReset`, `verifyEmail`,
`bookingConfirmation`, `stayReminder`, `bookingCancelledGuest`, `bookingPaid`,
`subscriptionActivated`, `subscriptionCancelled`, `subscriptionExpiringSoon`,
`sendContact`, `invoiceMail`.

## Notifications (`Services\NotificationService`)

Système étendu (voir migration_notifications_v2) : nouveaux types
d'événements, audience superadmin, push voyageurs sans compte via
`guest_push_subscriptions`, préférences utilisateur
(`users.notification_muted_types`, JSON), auto-déclenchement sans cron
(`SchedulerService`, déclenché sur requête HTTP authentifiée). SMS écarté
comme canal (décision produit). `NotificationService::broadcastToOwners()`
(2026-07-29) : annonce plateforme manuelle envoyée par un superadmin
(`/admin/notifications`) à tous les owners, type `platform_announcement`.

## Journal des évolutions récentes

- **2026-08-11** — `scripts/migre.sql` créé : fichier unique consolidant les
  migrations DB pas encore appliquées à la base **en ligne** (prod), à
  exécuter manuellement (ex. phpMyAdmin de l'hébergeur — pas d'accès
  shell/composer côté hébergeur, cf. section duplication `src/`↔`copy/`).
  - Périmètre déterminé par `git diff` de `copy/scripts/schema.sql` entre le
    dernier commit poussé sur `main` (`8899f7d`) et l'état local actuel — pas
    par une supposition sur les fichiers `migration_*.sql` un par un (ceux-ci
    ne sont jamais committés sur `main`, seul `schema.sql` consolidé l'est).
    Regroupe exactement `migration_plan_commission.sql` +
    `migration_geniuspay_fees.sql` + `migration_webauthn_login.sql` — les
    autres migrations du dossier (`migration_payouts.sql` etc.) sont déjà
    dans le schema committé, donc déjà en ligne.
  - **Vérifié par un test en base jetable** : schema du dernier commit chargé
    dans une base MySQL locale temporaire, `migre.sql` appliqué dessus, puis
    dump structure comparé au `schema.sql` local actuel — identiques
    caractère pour caractère (bases + dumps supprimés après vérification).
  - Contient encore la table `webauthn_login_credentials` bien que la
    fonctionnalité soit désactivée côté UI (voir entrée juste en dessous) —
    le code/les routes existent toujours et en ont besoin.
  - Resynchronisé dans `copy/`. **Ce fichier n'est qu'un artefact local**
    (comme les autres `migration_*.sql`, jamais committé sur `main`) — à
    exécuter manuellement sur la base en ligne, aucune automatisation.

- **2026-08-11** — Bug réel trouvé et corrigé sur le calcul de la commission
  plateforme (plan Starter, paiement en ligne) : l'établissement se voyait
  prélever le `commissionPct` entier (5%) sur le montant **majoré** déjà
  encaissé auprès du client, au lieu de `establishmentSharePct` (4%) sur le
  prix de **base**. Repéré par l'utilisateur sur les KPI de `/saas/payouts`
  ("Commission plateforme" affichait 1313 FCFA pour un encaissement de 26260
  FCFA, soit 5% du montant majoré — alors que le client ayant déjà payé 1% de
  majoration inclus dans ces 26260, l'établissement n'aurait dû en perdre que
  4%, soit 1300 FCFA, pour un solde disponible de 24960 et non 24947).
  - Cause : `Invoice::registerPayment()` calculait
    `commission_amount = $amount * $commissionPct / 100` directement sur
    `$amount`, qui est déjà le montant TTC **après** majoration client
    (`PlanGate::applyClientMarkup()`, appliqué en amont à la création de la
    réservation — le prix de base n'est jamais conservé séparément).
  - Fix : nouveau paramètre `float $clientSharePct = 0.0` sur
    `registerPayment()` ; le prix de base est retrouvé par
    `$amount / (1 + $clientSharePct/100)` avant d'appliquer `commissionPct`
    dessus. `BookingPaymentController::recordOnlinePayment()` (seul appelant
    concerné — les encaissements manuels/staff via `BookingController` et
    `InvoiceController` ne passent jamais de commission) transmet désormais
    `PlanGate::clientSharePct($estab)` en plus de `commissionPct($estab)`.
  - Vérifié en CLI PHP direct (`Models\PayoutRequest::availableBalance(1)`
    passe de 24947 à 24960 après correction) et par un appel isolé à
    `registerPayment()` confirmant `commission_amount = 1300` (au lieu de
    1313) pour les mêmes montants — testé avec un `booking_id` volontairement
    invalide pour ne rien insérer réellement (contrainte FK rejette
    l'insertion après calcul, aucun nettoyage nécessaire).
  - La seule ligne `payments` déjà en base avec ce bug (id=1, données de test
    de cette session) a été corrigée manuellement en DB locale
    (`commission_amount` 1313 → 1300) pour que le dashboard reflète
    immédiatement le calcul correct.
  - Resynchronisé dans `copy/`.

- **2026-08-09** — Refonte du système de plans : suppression du "vrai" plan
  gratuit. `config/plans.php` conserve le slug `starter` (nom affiché
  "Gratuit" → **"Starter"**) mais lui donne désormais **les mêmes
  fonctionnalités que Pro** (factures, paiements, dépenses, rapports, export
  PDF, chambres illimitées) — seule différence : le paiement en ligne des
  réservations y est **forcé actif, non désactivable**, avec une **commission
  plateforme de 10% par défaut** (éditable par l'admin) sur chaque paiement en
  ligne, alors que Pro/Business restent à 0% de commission (paiement en ligne
  optionnel, togglable).
  - **Découverte clé** : `establishments.online_payment_enabled` a toujours eu
    un défaut DB à `1` (aucun code ne le forçait à 0 pour `starter`) — le
    blocage venait uniquement du fait que le code confondait deux notions sous
    une seule feature `online_payment_control` : (1) l'établissement a-t-il
    accès au paiement en ligne, (2) le propriétaire peut-il le désactiver
    lui-même. Séparées en deux features distinctes dans `config/plans.php` :
    `online_payment` (accès, vrai pour les 3 plans désormais) et
    `online_payment_control` (contrôle du toggle, Pro/Business seulement,
    inchangé). `Core\PlanGate::commissionPct()` (nouveau) lit le taux effectif
    via `Services\PlanPricingService::commissionPct()`.
  - 4 points d'appel corrigés pour utiliser `online_payment` au lieu de
    `online_payment_control` : `BookingPaymentController::initiate()`,
    `PublicController::room()`, `PayoutController::gate()` (point critique :
    sans ce fix, un établissement Starter aurait pu encaisser via GeniusPay
    mais jamais demander de retrait). `EstablishController::update()` reste
    volontairement sur `online_payment_control` — c'est ce qui empêche déjà un
    propriétaire Starter d'éditer le toggle, sans code de blocage
    supplémentaire à écrire.
  - **Commission tracée par paiement** (pas seulement au retrait), pour rester
    stable même si le plan de l'établissement change ensuite : nouvelles
    colonnes `payments.commission_pct`/`commission_amount`
    (`scripts/migration_plan_commission.sql`, répercutées dans `schema.sql`),
    remplies dans `Invoice::registerPayment()` (nouveau paramètre optionnel,
    défaut 0 — les paiements manuels/espèces ne sont jamais commissionnés) via
    `BookingPaymentController::recordOnlinePayment()`. `PayoutRequest::
    availableBalance()` déduit désormais la commission totale du solde
    retirable (`totalCommission()`, nouveau) — le commentaire "aucune
    commission plateforme" de `migration_payouts.sql` n'est plus vrai, corrigé.
  - **Taux éditable par l'admin** (`/admin/settings`, réglage
    `plan_commission_starter_pct`), même pattern que les prix Pro/Business déjà
    éditables (`Services\PlanPricingService`/`AdminSettingsController`) —
    `PlanPricingService::effectivePlans()` répercute aussi ce taux effectif
    pour le tableau comparatif de `saas/docs.php`.
  - UI `/saas/settings` : le toggle "Paiement en ligne" est affiché **actif et
    non cliquable** pour Starter (nouvel état `onlinePaymentForced`, remplace
    l'ancien `onlinePaymentLocked` dont le sens "réservé Pro/Business" n'a plus
    lieu d'être — plus aucun plan n'est bloqué à l'accès désormais), avec le
    taux de commission affiché dynamiquement (passé en paramètre du composant
    Alpine depuis le serveur, pas codé en dur côté JS).
  - Vitrine (`pricing.php`, `home.php`, `contact.php`, `cgu.php`, etc.) et
    copy SaaS (`register.php`) mis à jour pour ne plus annoncer "gratuit" sans
    nuance — mention explicite de la commission partout où le plan Starter est
    présenté, y compris dans les CGU (article 5/6) pour rester juridiquement
    exact. `RoomController.php` : message d'erreur de limite de chambres
    dé-hardcodé de "plan Gratuit" (aucun plan n'a plus de limite de chambres
    désormais, ce gate devient vestigial mais reste en place si un futur plan
    en réintroduit une).
  - **`ONLINE_PAYMENTS_ENABLED` basculé à `true` en LOCAL** (`.env` de dev
    uniquement, jamais versionné/répliqué dans `copy/`, donc aucun effet en
    prod) pour permettre de tester le flux réel en sandbox GeniusPay (clés
    déjà en `pk_sandbox_`/`sk_sandbox_`, `APP_ENV=development`) — voir
    `online-payment-v1-lock` en mémoire auto. Le flag reste à `false` côté
    hébergeur ; l'activer en prod est une décision de déploiement séparée, à
    prendre explicitement après validation complète en local (et après avoir
    renseigné un vrai `GENIUS_PAY_WEBHOOK_SECRET`, actuellement vide).
  - **Bug trouvé juste après ce flip** : le lien de nav "Retraits" avait été
    retiré de `saas/layout.php` le 2026-07-23 (verrou v1 fermé) — jamais
    réintégré à la main comme le suggérait la mémoire d'alors. Une réservation
    test payée en ligne a bien enregistré sa commission en base
    (`payments.commission_amount` correct, vérifié directement en SQL), mais
    la page `/saas/payouts` restait inaccessible depuis l'UI (aucun lien),
    d'où l'impression que "la commission ne s'applique pas". Corrigé en
    rendant le lien **auto-réapparaissant** plutôt que remis à la main : les
    deux nav (sidebar desktop L207-256, `$moreTabs` mobile L679-684)
    conditionnent maintenant l'entrée "Retraits" sur `ONLINE_PAYMENTS_ENABLED`
    directement (`<?php if (ONLINE_PAYMENTS_ENABLED): ?>` / spread
    conditionnel `...(ONLINE_PAYMENTS_ENABLED ? [...] : [])`) — plus besoin
    d'y repenser à chaque futur flip du flag, dans un sens comme dans l'autre.
  - Validé par `php -l`/`node --check` sur tous les fichiers touchés, migration
    appliquée et vérifiée sur la base locale `hotel_sync` (colonnes présentes,
    tous les établissements déjà à `online_payment_enabled=1`), `schema.sql`
    revalidé par chargement complet dans une base jetable (structure
    identique). Logique testée en CLI PHP directe (`PlanGate::can()`/
    `commissionPct()`/`maxRooms()` pour starter vs pro : résultats conformes).
    Pas de test navigateur réel (paiement en ligne non activable dans cet
    environnement tant que `ONLINE_PAYMENTS_ENABLED=false`). Resynchronisé
    dans `copy/` (diff vide sur les arborescences touchées), **non commité/
    poussé depuis `copy/`** — décision de déploiement à valider explicitement.

- **2026-08-11** — Rapporté en testant : cliquer sur le bouton "Se connecter"
  (mot de passe classique, `submit()`) déclenche parfois le sélecteur natif
  de clé d'accès du navigateur ("Utiliser une clé d'accès enregistrée pour
  localhost"). **Confirmé que ce n'est pas notre code** : `submit()` ne fait
  qu'un `fetch()` simple, aucun appel `navigator.credentials` — c'est une
  suggestion native de Chrome/Edge (Gestionnaire de mots de passe Google),
  déclenchée par la simple présence de clés d'accès enregistrées pour cette
  origine et l'absence d'`autocomplete` explicite sur les champs email/mot de
  passe. Mitigation appliquée : `autocomplete="off"` sur les deux champs de
  `saas/login.php` — best-effort (comportement navigateur, pas garanti à
  100% par la spec), à revérifier après ce changement. Si ça persiste, seul
  un réglage utilisateur (Chrome → Mots de passe → Gestionnaire de mots de
  passe Google → désactiver la connexion automatique par clé d'accès) le
  supprime complètement.
  - Bouton secondaire "Se connecter avec l'empreinte" du formulaire classique
    signalé non visible — cause confirmée par l'utilisateur : couleurs
    codées en dur en style inline (`color:#1B4332`, bordure `rgba(27,67,50,
    0.15)` quasi transparente) pensées pour le fond clair desktop, invisibles
    sur le fond vert plein `.lg-right` du panneau mobile (`@media max-width:
    1024px`) — contrairement au reste du formulaire qui a ses propres
    surcharges de couleur pour ce mode. Fix : nouvelle classe `.lg-btn-secondary`
    (`login.css`) avec sa propre surcharge dans le bloc mobile (fond/bordure/
    texte clairs), plus le `.lg-spinner` associé (blanc par défaut, recoloré
    en sombre sur fond clair desktop, remis blanc sur fond vert mobile) — même
    piège que celui déjà documenté le 2026-07-29 pour le centrage flex/grid :
    un élément à fond transparent hérite silencieusement du fond de la page,
    à toujours vérifier explicitement dans les deux modes clair/sombre de ce
    fichier avant livraison.

- **2026-08-11** — Deuxième bug réel trouvé en testant : le sélecteur natif
  Windows Hello affichait, en plus de la vraie clé de connexion
  ("claude@gmail.com"), 4 entrées orphelines "device-xxxxxxxx" — vestiges de
  l'ancien gate "app installée" ([[pwa-app-only-login]], `WebauthnService`),
  qui utilisait `residentKey: 'required'` alors qu'il n'a **jamais** besoin
  d'une clé découvrable (il fonctionne par `device_token` opaque caché en
  localStorage, jamais par sélection humaine dans un menu). Confirmé :
  `webauthn_credentials` est **vide** en base (vidée par un `wipe-db.sql` à
  un moment du développement) mais Windows Hello garde ces clés
  indéfiniment — le stockage de clés d'accès de l'OS est totalement
  indépendant de notre base de données applicative.
  - Cause racine du mélange : les deux systèmes WebAuthn partagent le même
    RP id (`localhost`, via le trait commun) — normal et correct
    cryptographiquement (même origine), mais ça veut dire que TOUTES les
    clés découvrables pour cette origine, peu importe quel système les a
    créées, apparaissent ensemble dans le même sélecteur "usernameless" de
    [[webauthn-biometric-login]].
  - Fix : `WebauthnService::registrationOptions()`/`verifyRegistration()`
    passent `residentKey` de `'required'` à `'discouraged'` — les futures
    cérémonies du gate (s'il est un jour réactivé) ne pollueront plus le
    sélecteur de connexion réelle. N'affecte QUE les futurs enregistrements,
    ne nettoie pas les clés déjà orphelines côté Windows (aucune API web ne
    permet à une page de les supprimer — seul l'utilisateur peut le faire
    manuellement depuis les réglages Windows, purement cosmétique, sans
    impact fonctionnel puisque `verifyLogin()` rejette proprement toute clé
    absente de `webauthn_login_credentials`).
  - Resynchronisé dans `copy/`.

- **2026-08-11** — Bug réel trouvé et corrigé en testant la connexion rapide
  par empreinte (entrée juste en dessous) : après une activation réussie
  (confirmée en base, `webauthn_login_credentials` bien créée) puis une
  déconnexion pour retester `/login`, l'écran "connexion rapide" ne
  réapparaissait jamais. Cause : `saas.js::logout()` et le repli
  session-expirée (même fichier, ligne ~68) font `localStorage.clear()` —
  effaçant du même coup le témoin `biometric_login_hint` qui vient tout juste
  d'être posé. Même bug dans `admin.js` (espace superadmin, symétrique).
  - Fix : sauvegarder `biometric_login_hint` avant `localStorage.clear()`,
    le restaurer juste après, dans les 4 occurrences (`saas.js` × 2,
    `admin.js` × 2) — c'est un réglage de l'APPAREIL, pas de la session, il
    doit survivre à une déconnexion.
  - **Durci en plus** : `saas-settings.js::loadBiometricCredentials()`
    resynchronise désormais le témoin sur l'état réel du serveur à CHAQUE
    chargement de `/saas/settings` (présence d'au moins une empreinte active
    → pose le témoin, sinon le retire) — au lieu de ne le gérer qu'aux
    moments ponctuels d'activation/révocation. Auto-répare tout futur
    désalignement sans manipulation manuelle ; a rendu redondants (et donc
    retirés) les `localStorage.setItem`/`removeItem` explicites qui
    dupliquaient cette même logique dans `enrollBiometric()`/`revokeBiometric()`.
  - **Confirmation positive au passage** : `webauthn_login_credentials.last_used_at`
    de la ligne de test montrait une vraie connexion réussie antérieure — la
    cérémonie WebAuthn (enregistrement ET authentification) fonctionne bien de
    bout en bout en conditions réelles, pas seulement en théorie.
  - Resynchronisé dans `copy/`.

- **2026-08-11** — Écran "connexion rapide" (entrée juste en dessous)
  **redessiné en écran de verrouillage en deux temps**, sur référence visuelle
  fournie par l'utilisateur (captures d'un écran de verrouillage mobile
  générique) : (1) plein cadre vert forêt avec icône cadenas + "AfriStay
  verrouillé" + bouton "Déverrouiller", (2) au clic, un bas-de-page (fond
  `--mid`, coins arrondis façon bottom sheet) glisse avec "AfriStay" /
  "Déverrouillez pour accéder à votre espace hôtelier" / "Utiliser mes
  identifiants" / le bouton empreinte circulaire déjà existant
  (`.lg-bio-btn`, réutilisé tel quel). Remplace l'ancienne version
  "Bon retour parmi nous" à bouton unique (même déclencheur
  `biometricFirstMode`, nouvel état `lockRevealed` pour le 2ᵉ temps).
  - `.lg-lock` en `position:fixed;inset:0` : recouvre toute la mise en page
    (grille desktop deux panneaux incluse) par un plein cadre unique, cohérent
    avec la référence — contrairement au reste de la page, pas de variante
    "mode clair desktop" à prévoir pour ce nouvel écran : toujours vert forêt
    plein, quel que soit desktop/mobile.
  - Réutilise `.lg-bio-btn`/`.lg-bio-fallback` existants (`loginWithBiometric()`/
    `useCredentialsInstead()` inchangés) — seule une classe `.lg-lock-fallback`
    ajoutée pour recolorer "Utiliser mes identifiants" en clair sur le fond
    `--mid` du bas-de-page (même piège de couleur figée que celui corrigé
    juste avant sur le bouton secondaire du formulaire classique — vérifié
    cette fois dès l'écriture plutôt qu'après coup).
  - **Bug réel trouvé au premier test** : le bas-de-page remontait collé en
    haut de l'écran au lieu de rester ancré en bas. Cause : `.lg-lock` est un
    conteneur flex-column avec `.lg-lock-top` (flex:1, pousse le reste vers le
    bas) et `.lg-lock-sheet` (`margin:0 auto`, centrage horizontal seulement)
    comme deux enfants — au clic sur "Déverrouiller", `.lg-lock-top` disparaît
    du flux (`x-show`→`display:none`), donc plus rien ne pousse `.lg-lock-sheet`
    vers le bas. Fix : `margin: auto auto 0` (au lieu de `0 auto`) — ancre le
    bas-de-page au bas de `.lg-lock` de façon indépendante de la présence de
    son ancien voisin flex, plutôt que de compter sur lui pour le pousser.
  - Resynchronisé dans `copy/` après ce fix.

- **2026-08-11** — Nouveau visuel pour le premier écran (`.lg-lock-top`) de
  l'écran verrouillé façon écran de verrouillage mobile, sur référence visuelle
  fournie par l'utilisateur : cadenas plein en dégradé or (au lieu de l'ancien
  tracé ligne blanche), entouré de deux anneaux dorés concentriques
  (`.lg-lock-ring-1/2`) et de petits points dispersés façon éclat
  (`.lg-lock-dot-1` à `-6`, positions fixes en CSS) — même esprit décoratif que
  `.lg-radial`/`.lg-ghost` déjà présents sur cette page. Sous-titre ajouté sous
  le titre "AfriStay verrouillé" ("Déverrouillez pour accéder à votre espace
  hôtelier sécurisé") — absent du premier écran jusqu'ici, présent seulement
  dans le bas-de-page du second temps.
  - `src/templates/saas/login.php` : `.lg-lock-icon` seul → `.lg-lock-badge`
    (anneaux + points + icône), SVG cadenas plein avec `<linearGradient
    id="lgLockGrad">` (or clair → bronze).
  - `public/assets/css/pages/login.css` : nouvelles classes `.lg-lock-badge`,
    `.lg-lock-ring`, `.lg-lock-dot-*`, `.lg-lock-subtitle` ; `.lg-lock-icon`
    gagne un `filter: drop-shadow(...)` pour détacher le cadenas du fond vert.
  - Purement visuel, aucun changement de logique JS. `php -l` OK, resynchronisé
    dans `copy/`.
  - **Rendu à confirmer par l'utilisateur en navigateur réel** (même réserve
    habituelle pour tout ce qui touche `/login`).
  - Suite (même jour) : capture réelle montrant le second temps (bas-de-page
    `.lg-lock-sheet`, `lockRevealed = true`) — grand vide vert au-dessus, et
    capteur d'empreinte quasi invisible (`.lg-bio-btn` en dégradé
    `forest→mid`, sur un fond de bas-de-page déjà `var(--mid)` : les deux se
    confondaient). Corrections :
    - `.lg-lock-radial`/`.lg-lock-ghost` ajoutés en fond permanent de
      `.lg-lock` (toujours dans le DOM, sans `x-show`) pour combler le vide
      au-dessus du bas-de-page — même halo doré/lettrage fantôme "AS" que
      `.lg-left` en desktop.
    - Capteur d'empreinte du bas-de-page repris en dégradé or
      (`#EAD08A→#B4872F`, icône vert forêt pour le contraste, au lieu de
      blanc sur vert) et entouré d'un halo à deux anneaux
      (`.lg-lock-sheet-badge`, même motif que le badge du cadenas) pour lui
      donner du poids au lieu de flotter seul.
    - Petit liseré doré ajouté en tête du bas-de-page (`.lg-lock-sheet::before`,
      façon poignée de tiroir) pour casser l'angle nu.
  - Resynchronisé dans `copy/`. Toujours à confirmer en navigateur réel.
  - Suite (même jour) : capture réelle montrant que la zone haute reste
    perçue comme vide malgré le halo/lettrage fantôme (`.lg-lock-ghost` à
    0.05 d'opacité, quasi invisible en pratique). Cause racine : `.lg-lock-top`
    était retiré du flux par `x-show="!lockRevealed"`, donc plus aucun
    élément flex ne centrait quoi que ce soit dans le vide au-dessus du
    bas-de-page une fois relevé — un simple halo de fond ne suffisait pas à
    donner l'impression d'un écran "conçu".
    - Restructuré : `.lg-lock-top` reste **toujours monté** (vrai `flex:1`,
      centre son contenu nativement), son contenu bascule via deux enfants
      `x-show` : `.lg-lock-face` (cadenas + titre + sous-titre + bouton,
      verrouillé) et `.lg-lock-mark` (monogramme "AS" à double anneau doré +
      légende "Espace hôtelier sécurisé", bas-de-page relevé) — même motif
      d'anneaux que `.lg-lock-badge`/`.lg-lock-sheet-badge`, cohérence
      visuelle entre les trois temps de l'écran.
    - `.lg-lock-ghost` (lettrage géant inefficace) supprimé, remplacé par ce
      contenu réellement visible ; `.lg-lock-radial` conservé (légère lueur
      d'ambiance, opacité montée à 0.16).
  - Resynchronisé dans `copy/`. Toujours à confirmer en navigateur réel.

- **2026-08-11** — Écran "connexion rapide" par empreinte façon WhatsApp sur
  `/login`, en complément de la connexion biométrique ajoutée plus tôt le même
  jour (entrée juste en dessous). Si l'empreinte a déjà été activée sur
  l'appareil courant, elle passe désormais **en priorité** devant le
  formulaire classique (gros bouton circulaire tappable, pas de déclenchement
  automatique de la cérémonie — évite les erreurs navigateur sur les OS qui
  exigent un geste utilisateur explicite pour `navigator.credentials.get()`).
  Toujours réversible via un bouton "Utiliser mes identifiants" — ne bloque
  jamais l'accès au mot de passe, cohérent avec l'exigence "jamais un
  remplacement" déjà posée pour cette fonctionnalité.
  - Détection : pas de nouvel appel serveur pré-connexion (impossible de
    savoir de façon fiable si CET appareil a un passkey sans déclencher la
    cérémonie) — un simple indice `localStorage['biometric_login_hint']`,
    posé par `saas-settings.js::enrollBiometric()` après une activation
    réussie, et retiré par `revokeBiometric()` si c'était la dernière
    empreinte du compte (sinon `/login` continuerait de proposer un chemin
    voué à l'échec).
  - `src/templates/saas/login.php` : nouvel écran (`.lg-gate` réutilisé pour
    la mise en page, nouveau `.lg-bio-btn` circulaire + `.lg-bio-fallback`
    dans `login.css`), affiché si `isApp && biometricFirstMode` ; le
    formulaire classique passe à `isApp && !biometricFirstMode`. `login.js` :
    nouvel état `biometricFirstMode` (lu une fois depuis localStorage à
    l'init) + `useCredentialsInstead()` (bascule manuelle).
  - Resynchronisé dans `copy/`.
  - **Non testable sans navigateur réel avec authentificateur** — même
    réserve que l'entrée précédente.

- **2026-08-11** — Connexion par empreinte digitale **désactivée** à la
  demande explicite de l'utilisateur, après les retouches visuelles
  ci-dessus. Masquage pur (pas de suppression de code), sur le même principe
  que `ONLINE_PAYMENTS_ENABLED` : backend (`WebauthnLoginService`, routes,
  table `webauthn_login_credentials`) intact et réactivable en un flag.
  - `public/assets/js/pages/login.js` : nouvelle constante
    `BIOMETRIC_LOGIN_ENABLED = false` en tête de fichier ; `biometricFirstMode`
    (écran verrouillé prioritaire) et la détection dans `init()`
    (`biometricAvailable`, bouton secondaire) passent tous les deux par ce
    flag — repassé à `true`, tout redevient actif sans autre changement.
  - `public/assets/js/pages/saas-settings.js` : même flag, gate
    `biometricSupported` (carte d'activation dans Paramètres → Compte →
    Sécurité).
  - Aucun changement PHP/template : les trois points d'entrée UI
    (`.lg-lock`, bouton secondaire `/login`, carte `/saas/settings`)
    utilisent déjà des `x-show` sur ces variables, donc les masquer côté JS
    suffit à tout cacher.
  - Resynchronisé dans `copy/`.

- **2026-08-11** — Connexion optionnelle par empreinte digitale / Face ID /
  Windows Hello (passkey), en complément du mot de passe — **jamais un
  remplacement ni une obligation**, à la demande explicite de l'utilisateur.
  Point d'histoire important : ce projet avait déjà un mécanisme WebAuthn,
  mais pour l'usage inverse (gate "app installée uniquement", cérémonie
  obligatoire à chaque connexion, retiré le 2026-07-17 pour friction — voir
  `pwa-app-only-login`/`feedback_security_ux_friction` en mémoire auto). Cette
  fois c'est un raccourci qui **réduit** la friction, jamais un blocage.
  - **`WebauthnService.php` (gate existant) intentionnellement pas touché** —
    anonyme par conception (`webauthn_credentials` sans `user_id`), usage
    différent. Nouveau système séparé, lié à un vrai compte.
  - Config RP/cérémonie/gestion des défis (`rpId`, `rp`, `ceremonyFactory`,
    `serializer`, `storeChallenge`/`consumeChallenge`, etc.) extraite dans un
    nouveau trait `Services\WebauthnRelyingPartyTrait` — pure extraction
    depuis `WebauthnService.php` (comportement du gate existant inchangé,
    revérifié par appel direct après refactor), réutilisée par le nouveau
    `WebauthnLoginService`. Évite ~40 lignes de config sécurité dupliquées
    entre les deux services (risque réel de dérive sinon).
  - Nouvelle table `webauthn_login_credentials` (`user_id` NOT NULL, FK
    CASCADE vers `users`, `credential_id` UNIQUE, `aaguid`, `public_key`,
    `user_handle`, `counter`, `device_label` — même heuristique que
    `user_sessions.device_label`, via `AuthService::deviceLabel()` passée de
    `private` à `public`). `webauthn_challenges.type` étendu
    `('register','login')` → `('register','login','login_register')` —
    `'login'` existait déjà dans le schéma sans jamais avoir été câblé.
  - **Connexion "discoverable"** (sans email préalable) : `allowCredentials`
    vide à la connexion, le navigateur propose directement les passkeys
    disponibles pour le site ; le compte est retrouvé via le `credential_id`
    renvoyé par le navigateur (pas besoin de décoder le `user_handle` WebAuthn
    pour ça). `authenticatorAttachment: 'platform'` à l'enrôlement (biométrie
    intégrée à l'appareil uniquement, pas de clé de sécurité USB — cohérent
    avec "empreinte digitale"), `userVerification: 'required'` partout
    (enregistrement ET connexion) pour imposer une vraie vérification
    biométrique/PIN, pas juste "appareil présent".
  - `WebauthnLoginVerify` construit le JWT via `AuthService::encode()` avec
    exactement le même payload que `login()` (mot de passe) — le frontend
    (`login.js`) réutilise sa logique de stockage token/redirection sans
    distinction, factorisée dans un nouveau `handleLoginSuccess()` partagé
    (élimine la duplication qui existait entre les deux chemins).
  - `pwa.js` : les helpers `b64urlToBuffer`/`bufferToB64url` (déjà utilisés en
    interne par le gate existant) exposés sur `window.AfristayPWA` pour être
    réutilisés par `saas-settings.js` (enrôlement) et `login.js` (connexion)
    sans dupliquer ces conversions dans deux fichiers de plus.
  - UI : nouvelle carte "Connexion par empreinte digitale" dans `/saas/settings`
    (onglet Compte, sous "Appareils connectés", même style de liste),
    masquée si le navigateur ne supporte pas WebAuthn. Bouton "Se connecter
    avec l'empreinte" sur `/login`, affiché seulement si
    `PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable()`
    résout `true` (détecte un authentificateur biométrique réel, pas juste le
    support de l'API WebAuthn en général).
  - Endpoints publics (`login-options`/`login-verify`) rate-limités par IP,
    même pattern que `login()` classique.
  - **Validé en conditions réelles (backend uniquement)** : cycle complet
    testé via l'API réelle — génération des options d'enrôlement/connexion,
    frontière IDOR vérifiée explicitement (un autre utilisateur ne peut ni
    lister ni révoquer l'empreinte d'un compte qui n'est pas le sien, 404 même
    message que "introuvable" pour ne pas fuiter l'existence), révocation par
    le vrai propriétaire opérationnelle, gate app-only existant revérifié
    intact après le refactor du trait. **La cérémonie WebAuthn elle-même
    (`navigator.credentials.create()`/`get()`, boîte de dialogue biométrique)
    n'a PAS pu être testée** — impossible à simuler en CLI/curl contrairement
    aux paiements GeniusPay, nécessite un vrai navigateur avec authentificateur
    (empreinte/Face ID/Windows Hello) pour une validation de bout en bout.
  - Resynchronisé dans `copy/`.

- **2026-08-10** — Correction du calcul des frais GeniusPay à l'encaissement :
  le forfait de **100 XOF est prélevé D'ABORD, puis les 2.5% sont calculés sur
  le montant RESTANT** (pas sur le montant brut), précision donnée
  explicitement. `GeniusPayService::paymentFee()` : `100 + max(0, montant −
  100) × 2.5%` au lieu de `100 + montant × 2.5%`. Écart faible mais réel (sur
  15150 FCFA : 476,25 au lieu de 478,75) — revérifié en conditions réelles sur
  le sandbox, valeur exacte confirmée. `withdrawalFee()` (1% au retrait)
  inchangée, la précision ne portait que sur l'encaissement. Resynchronisé
  dans `copy/`.

- **2026-08-10** — Commission Starter recalculée : **7% → 5% au total**, **1.5%
  → 1% côté client**, **5.5% → 4% côté établissement**, suite à la précision
  donnée sur les frais réels GeniusPay (entrée juste au-dessus). Nouvelle
  logique du taux, calée explicitement sur les coûts réels + une marge visée :
  GeniusPay prend 2.5% à l'encaissement (1.5 pt absorbé par la plateforme, 1 pt
  répercuté au client) + 1% au retrait (absorbé par la plateforme) + 1.5% de
  marge plateforme voulue = **5% de commission totale, dont 1% côté client**.
  - Seul `config/plans.php` a changé (`online_payment_commission_pct: 7→5`,
    `online_payment_client_share_pct: 1.5→1`) — tout le reste (vitrine,
    `saas/settings.php`, `saas/docs.php`, CGU, admin) lit dynamiquement via
    `PlanPricingService`, donc s'est mis à jour sans aucune autre modif de
    code. Seuls deux commentaires codaient "7%" en dur (docblocks de
    `BookingPaymentController::recordOnlinePayment()` et
    `PayoutRequest::availableBalance()`), corrigés en "5%".
  - **Le forfait fixe de 100 XOF de GeniusPay n'est PAS inclus dans ce
    pourcentage** — il reste uniquement tracé de façon informative
    (`payments.geniuspay_fee_amount`, `AdminController::platformMargin()`,
    cf. entrée précédente). Sur une petite réservation, ce forfait fixe mange
    une partie de la marge de 1.5% visée par le taux — revérifié par calcul
    réel (booking 15150 FCFA : marge nette réelle 134,82 FCFA, soit ~0,89% au
    lieu des 1.5% théoriques asymptotiques, à cause du 100 XOF fixe qui pèse
    plus sur ce montant qu'un gros panier).
  - **Validé en conditions réelles** (2ᵉ passage) : nouvelle réservation
    payée en sandbox à 15150 FCFA (15000 × 1.01) → `commission_pct=5.00`,
    `commission_amount=757.50` (exact), `geniuspay_fee_amount=478.75` (100 +
    15150×2.5%, exact). Solde établissement recalculé correctement
    (`gross_online_collected=30150`, `total_commission=2257.5` — combine
    l'ancien paiement à 10% et le nouveau à 5%, chacun figé à son taux
    d'origine comme prévu). Données de test nettoyées.
  - Aucune migration nécessaire (mêmes colonnes, juste la config par défaut).
    Resynchronisé dans `copy/`.

- **2026-08-10** — Frais réels GeniusPay tracés (purement informatif, aucun
  montant facturé/versé ne change), suite à une précision donnée sur les vrais
  coûts de la passerelle : **100 XOF + 2.5% à l'encaissement**, **1% au
  retrait**. Objectif : donner au superadmin la marge nette réelle de la
  plateforme (commission encaissée moins les frais GeniusPay), distincte de la
  commission brute déjà affichée partout (7%/1.5%/5.5%, cf. entrée du dessus).
  - `Services\GeniusPayService::paymentFee()`/`withdrawalFee()` (nouveau) —
    colocalisé avec le reste du code GeniusPay plutôt qu'un nouveau service.
  - Nouvelles colonnes (`scripts/migration_geniuspay_fees.sql`, répercutées
    dans `schema.sql`) : `payments.geniuspay_fee_amount`,
    `payout_requests.geniuspay_fee_amount`. Calculées à l'encaissement
    (`Invoice::registerPayment()`, nouveau paramètre `bool $viaGeniusPay` —
    **uniquement pour les paiements réellement passés par la passerelle**, un
    encaissement manuel espèces/mobile money saisi par le personnel reste à
    0) et à la création d'une demande de retrait (`PayoutController::store()`).
  - **Important, vérifié explicitement** : `PayoutRequest::availableBalance()`
    n'est PAS modifié — l'établissement voit et retire exactement le même
    montant qu'avant, ces frais ne réduisent ni le prix client ni le net
    établissement, contrairement à la commission plateforme elle-même.
  - `AdminController::overview()` — nouveau bloc `platform_margin` (commission
    encaissée, frais GeniusPay encaissement, frais GeniusPay retrait
    [seulement les demandes déjà `'paid'`], marge nette). Nouvelle carte
    "Marge nette plateforme" sur `/admin` (6ᵉ carte KPI, grille passée de
    `repeat(5,1fr)` à `repeat(6,1fr)` dans `admin.css`).
  - Portée : appliqué à **tous les plans** (pas seulement Starter) — c'est un
    coût réel de la passerelle sur chaque paiement en ligne, indépendant de la
    commission plateforme (qui elle reste 0% pour Pro/Business).
  - **Validé en conditions réelles** : réservation payée en sandbox →
    `geniuspay_fee_amount = 480.63` (= 100 + 15225×2.5%, exact) ; demande de
    retrait de 10 000 → `geniuspay_fee_amount = 100` (= 1%, exact) ; une fois
    le retrait marqué payé, `/api/admin/overview` a renvoyé
    `net_margin = 1985.12` (= 2565.75 commission − 480.63 − 100, exact).
    Données de test nettoyées après vérification. Resynchronisé dans `copy/`.

- **2026-08-10** — Idempotence des deux flux de confirmation de paiement GeniusPay
  (réservations en ligne ET abonnements SaaS), suite à un audit demandé
  explicitement. Constat avant fix : `BookingPaymentController::callback()`
  (webhook) et `SubscriptionController::callback()` faisaient un
  `UPDATE ... WHERE id = ?` inconditionnel puis envoyaient l'email/notification
  sans aucune garde — un rejeu de webhook (retry standard chez GeniusPay en cas
  de non-réponse rapide) ou une course avec `verify()` (poll client) pouvait
  dupliquer la ligne `payments`/déclencher la commission agent en double, et
  garantissait un email de confirmation renvoyé à chaque rejeu. `Core\Database`
  n'expose aucune primitive de verrou/transaction (`beginTransaction`,
  `FOR UPDATE`) — confirmé par lecture directe.
  - **Fix** : `UPDATE` conditionnel sur l'état actuel
    (`WHERE pay_status != 'paid'` / `WHERE status != 'active'`) + vérification
    de `PDOStatement::rowCount()`. Une seule requête `UPDATE` est atomique par
    construction MySQL (verrouillage de ligne implicite) même sans transaction
    explicite — un seul appelant obtient `rowCount() > 0`, tous les autres
    (rejeu, course) deviennent des no-op silencieux. Marche aussi bien sur
    `bookings` (InnoDB) que `subscriptions` (MyISAM, verrou de table plutôt que
    de ligne mais même garantie d'atomicité par requête).
  - `BookingPaymentController::confirmAndNotify()` (déjà existante, utilisée
    par `verify()`) reçoit la garde + est réutilisée par `callback()` (qui
    dupliquait la même logique inline avant) — un seul point d'entrée
    désormais. **Bug additionnel corrigé au passage** : `confirmAndNotify()`
    appelait `MailService::bookingPaid($booking)` avec le `$booking` reçu tel
    quel, qui n'a jamais `client_email` selon l'appelant (`verify()` ne
    sélectionne que `room_number`/`establishment_name`, pas les infos client)
    → `bookingPaid()` abandonne silencieusement faute de destinataire
    (`if (!$to) return;`). Corrigé en rechargeant systématiquement
    `Booking::findWithDetails()` (source complète, client_email inclus) avant
    l'envoi, quel que soit l'appelant.
  - `SubscriptionController` : logique d'activation (webhook `callback()` et
    poll `verify()`, ~80 lignes dupliquées à l'identique) extraite dans une
    nouvelle méthode privée `activateSubscription()` avec la même garde
    atomique — élimine la duplication ET l'idempotence en un seul refactor.
    Le chemin "crédit de prorata couvre tout" dans `initiate()` (INSERT direct
    en `active`, requête synchrone unique côté utilisateur authentifié, pas de
    webhook/course possible) volontairement laissé tel quel — pas le même
    risque.
  - **Validé en conditions réelles** : réservation test créée, paiement
    initié en sandbox GeniusPay, **webhook envoyé 3 fois de suite** (simulation
    retry) → une seule ligne `payments` en base, `paid_at` stable (ne bouge
    plus à chaque rejeu, avant il était réécrit à `NOW()` à chaque appel).
    Données de test nettoyées après vérification.
  - Resynchronisé dans `copy/`.

- **2026-08-10** — Commission Starter revue à la baisse et répartie entre client
  et établissement, sur demande explicite : 10% → **7% au total**, dont
  **1.5% côté client** (inclus dans le prix affiché/facturé, pas de ligne de
  frais séparée, même prix quel que soit le mode de paiement finalement
  choisi — décision produit explicite) et **5.5% côté établissement**
  (prélevés sur le montant collecté au paiement en ligne, comme avant).
  - `config/plans.php` : nouvelle clé `online_payment_client_share_pct` (1.5
    pour starter, 0 pour pro/business) à côté de `online_payment_commission_pct`
    (7 au lieu de 10). `Services\PlanPricingService` : `clientSharePct()`,
    `establishmentSharePct()` (= commissionPct - clientSharePct, jamais
    stockée séparément pour éviter toute dérive) et `applyClientMarkup()`
    (nouveau point d'entrée unique). `Core\PlanGate` : miroir
    `clientSharePct()`/`applyClientMarkup()`, même pattern que
    `commissionPct()` existant. Taux éditables séparément en admin
    (`plan_commission_starter_pct` / `plan_commission_starter_client_pct`,
    validation croisée : la part client ne peut pas dépasser le taux total).
  - **Simplification assumée** : `commission_amount` déduite au retrait reste
    calculée sur le montant réellement collecté (déjà majoré de 1.5%), PAS
    reconstituée sur le prix de base avant majoration — plus simple/robuste
    qu'un calcul inverse, au prix d'un écart de ~0.1 point (établissement perd
    ~5.6% de son prix de base au lieu de 5.5% pile). Vérifié par calcul direct
    en CLI PHP : base 15000 → prix client 15225, commission 1065.75, net
    établissement 14159.25.
  - **Majoration appliquée à la source unique du prix** plutôt que dupliquée :
    `PublicController::room()`/`property()` majorent directement
    `base_price`/`weekend_price`/`passage_price` avant de les renvoyer — comme
    `booking.js` calcule déjà son estimation à partir de ces mêmes champs
    (constaté par exploration du code), aucune modification JS n'a été
    nécessaire pour que l'estimation client reflète la majoration. Idem
    `search()`/`establishments()` sur `min_price` (post-traitement PHP après
    la requête SQL, établissement par établissement). `PublicController::
    bookingRequest()` applique la majoration une seule fois sur le montant
    final (`Booking::calculateAmount()` puis `PlanGate::applyClientMarkup()`)
    avant de créer la réservation/facture — **`Booking::calculateAmount()`
    lui-même n'est volontairement pas touché** (fonction partagée avec
    `BookingController::store()`, création interne SaaS par le personnel, qui
    ne doit pas subir cette majoration ni le concept de "prix public").
  - Textes mis à jour pour détailler la répartition (pas juste le taux total) :
    `vitrine/pricing.php` (carte Starter + FAQ), `vitrine/cgu.php` (article 5),
    `saas/settings.php` (message du toggle forcé, cadre le "manque à gagner"
    réel pour l'établissement à 5.5% et non 7%), `saas/docs.php` (nouvelle
    ligne "dont incluse dans le prix client" dans le tableau comparatif),
    `admin/settings.php` (second champ "part client" à côté du taux total).
  - Validé par `php -l`/`node --check` sur tous les fichiers touchés et calcul
    manuel en CLI PHP (ci-dessus). Pas de nouvelle migration SQL (les colonnes
    `payments.commission_pct`/`commission_amount` existaient déjà, seule la
    valeur configurée change — les paiements déjà enregistrés à l'ancien taux
    10% gardent leur `commission_pct` figé, comportement voulu). Resynchronisé
    dans `copy/` (diff vide).

- **2026-08-09** — Fix `vitrine/search.php` : erreur console Alpine
  (`Cannot read properties of undefined (reading 'toFixed')`) sur
  `p._distanceKm.toFixed(1)`, dans les deux vues (grille L125, liste L159).
  Cause : `x-show` masque visuellement l'élément mais n'empêche pas Alpine
  d'évaluer le `x-text` voisin — `_distanceKm` n'existe même pas sur les
  objets tant que `geoActive` est `false` (état par défaut à l'arrivée sur la
  page, voir `search.js::displayedResults` qui ne l'attache que si le filtre
  "à proximité" est actif). Fix : garde redondante directement dans
  l'expression `x-text` (`p._distanceKm != null ? (...) : ''`), ne plus
  compter sur `x-show` pour protéger un `x-text` voisin. Vérifié qu'aucun
  autre `x-text` avec `.toFixed(` dans le projet n'a le même risque
  (`saas/settings.php:168` est protégé par un vrai `x-if`, qui retire
  l'élément du DOM contrairement à `x-show` — pas de bug là).

- **2026-08-04** — Contenu du site Docusaurus **entièrement réécrit**, à la
  demande explicite de l'utilisateur ("retire les ancien fichier de
  documentation dans le docusaurus fait un nouveau complet"), quelques
  minutes après la mise en place initiale (entrée précédente juste en
  dessous). Les 9 fichiers migrés depuis `documentation/*.md` (portage brut
  d'anciens audits, certains datés de fin juin/mi-juillet 2026 et déjà
  signalés comme partiellement obsolètes par leur propre contenu) ont été
  **supprimés** du site et remplacés par 9 nouvelles pages écrites
  directement à partir du code actuel (`config/routes.php`, `config/
  plans.php`, `src/Core`, les 26 contrôleurs/23 modèles/14 services de
  `src/Controllers`|`Models`|`Services`, `scripts/schema.sql` — 37 tables)
  plutôt que copiées depuis d'anciens rapports : `intro`,
  `architecture/{overview,backend,frontend,database}`, `auth-securite`,
  `roles-plans`, `fonctionnalites`, `api-reference`, `deploiement`.
  - Cette relecture directe du code a mis en évidence l'ampleur de la dérive
    des anciens fichiers `documentation/` (jamais mis à jour après leur
    session initiale de mi-juillet) : 26 contrôleurs actuels contre 11
    documentés, 23 modèles contre 9, 37 tables contre ~20 — tout le pan
    "agents commerciaux", "réglages plateforme éditables" (`Core\Settings`,
    `platform_settings`), primes agents ponctuelles (`agent_bonus_awards`),
    retraits (`payout_requests`/`PayoutController`), sauvegardes admin
    (`BackupService`), newsletter, messages de contact et annonces vitrine
    n'y figurait pas du tout.
  - Sidebar (`sidebars.js`) reconstruite en conséquence (catégorie
    "Architecture" à 4 pages + 6 pages de premier niveau) ; liens de pied de
    page (`docusaurus.config.js`) mis à jour vers les nouveaux chemins.
    `npm run build` revalidé sans erreur après la réécriture (les liens
    croisés entre nouvelles pages utilisent des chemins de doc simples,
    volontairement sans ancre de titre fragile — les ancres Docusaurus ne
    sont pas vérifiées au build, une ancre fausse resterait silencieusement
    cassée).
  - `documentation/*.md` (à la racine du dépôt) **n'a pas été touché** —
    seul le contenu déjà copié dans `docs-site/` a été retiré ; ces fichiers
    restent disponibles tels quels comme archive de l'audit initial.

- **2026-08-04** — Site de documentation **Docusaurus** ajouté (`docs-site/`), à la
  demande explicite de l'utilisateur ("utilise docusaurus pour documenter le
  projet"). Décisions confirmées par l'utilisateur avant implémentation :
  **local uniquement** (non versionné, même convention que `documentation/`)
  et **migration du contenu existant** plutôt qu'un site vide.
  - Scaffold `create-docusaurus@latest classic --javascript` dans `docs-site/`
    (npm). **Piège rencontré à l'install** : le tout premier `npm install`
    (déclenché par le scaffold) a échoué silencieusement en laissant
    `node_modules/` partiellement vide (`docusaurus` introuvable ensuite), et
    même après un `npm install` réussi en apparence, le build échouait avec
    `Cannot find module '@rspack/binding-win32-x64-msvc'` (dépendance
    optionnelle native de `@docusaurus/faster` non installée — bug connu npm
    sur Windows avec les dépendances optionnelles). Fix : suppression complète
    de `node_modules/` + `package-lock.json` puis `npm install` propre depuis
    zéro — plus fiable qu'un simple re-install par-dessus un état corrompu.
    `npm run build` validé sans erreur ni warning après ce fix.
  - **Mode "docs-only"** : `docusaurus.config.js` (`docs.routeBasePath: '/'`,
    `blog: false`), page d'accueil par défaut (`src/pages/index.js` +
    `HomepageFeatures`) et démo blog supprimées — `docs/intro.md`
    (`slug: /`) sert de page d'accueil. Locale unique `fr`. Logo repris de
    `logo.png` (racine du dépôt) pour la navbar.
  - **Contenu migré** depuis `documentation/*.md` (9 fichiers) via un script
    Node ponctuel (`migrate-docs.js`, non conservé dans le dépôt — un
    one-shot, pas un outil réutilisable) : ajout de frontmatter
    (titre/`sidebar_position`), réécriture des liens croisés entre ces
    fichiers vers leurs nouveaux chemins Docusaurus, et **désamorçage des
    liens vers des fichiers source du dépôt** (ex.
    `[src/Models/User.php:21-28](src/Models/User.php#L21-L28)` dans
    `FICHE_SECURITE.md`/`ACCES_PROFILS_ABONNEMENT.md`) en texte `code`
    simple — ces chemins ne sont pas résolvables par Docusaurus
    (`onBrokenLinks: 'throw'` les aurait fait échouer au build). Sidebar
    manuelle (`sidebars.js`, pas d'autogénération) organisée en catégories :
    Architecture, Sécurité & accès, Fonctionnel, Parcours utilisateurs
    (audits historiques SaaS/B2C de fin juin 2026, à distinguer de
    `FICHE_FONCTIONNELLE.md` qui fait foi sur l'état actuel), Journal
    (`modif1.md` → historique de la toute première session).
  - `.gitignore` racine : ajout de `/docs-site/` (node_modules déjà couvert
    par la règle générique existante, mais config/sidebar/sources du site ne
    l'étaient pas) — cohérent avec `*.md` qui exclut déjà tout
    `documentation/`.
  - **Non répliqué dans `copy/`** : `docs-site/` est un outil de
    documentation interne, pas un artefact déployé sur l'hébergeur (contexte
    différent de la règle habituelle de resynchronisation `src/public/config/
    scripts` ↔ `copy/`).
  - Pour lancer : `cd docs-site && npm start` (dev, `localhost:3000`) ou
    `npm run build` (site statique dans `docs-site/build/`).

- **2026-07-29** — Annonce vitrine repensée en **popup** au lieu du bandeau
  fixe en haut de page (deux itérations de design ce même jour avant
  celle-ci, cf. entrée suivante — le bandeau centré n'a pas convaincu, le
  produit voulait un popup avec l'identité "Afri Stay"). `vitrineAnnouncementBanner()`
  (vitrine.js) remplacée par `vitrineAnnouncementPopup()` — même logique de
  chargement/mémorisation de fermeture par ID (`localStorage
  afristay_announcement_dismissed`), mais affichage en **overlay modal**
  (fond assombri + flou, carte centrée wordmark "Afri Stay" + icône 📢 +
  titre/message + bouton "Compris") plutôt qu'en bandeau qui décalait la
  mise en page. Conséquence en cascade : le mécanisme `Alpine.store(
  'vitrineChrome', { bannerHeight })` qui décalait le `top` de la nav
  flottante (ajouté pour le bandeau) devenait inutile — supprimé, nav
  revenue à ses valeurs `top` fixes d'origine (16px replié / 0 déplié).
  - **Piège d'environnement découvert et à retenir pour tout futur écran
    de ce projet** : un `display:flex;align-items:center;justify-content:
    center` sur l'overlay ne centrait PAS la carte modale en test réel
    (carte collée en haut-gauche, alors que le CSS semblait correct à la
    lecture) — exactement le même symptôme que le bug des graphiques
    admin du jour (colonnes qui s'empilaient au lieu de s'aligner en
    ligne). Fix identique : remplacer par `display:grid;place-items:
    center`. Deux occurrences du même jour suggèrent un vrai problème
    (pas juste une faute de frappe isolée) avec `align-items`/
    `justify-content` en centrage flex dans cet environnement de rendu —
    **réflexe à adopter systématiquement à partir de maintenant : préférer
    CSS Grid (`place-items`/`grid-template-columns` explicite) à flexbox
    dès qu'un centrage ou une répartition en colonnes est nécessaire**,
    plutôt que flex + `align-items`/`justify-content`, qui s'est avéré
    non fiable deux fois de suite ici. Revérifié en réel après fix (capture
    desktop 1400px + mobile 390px + test du bouton "Compris" avec
    persistance après rechargement de page) : centrage correct partout,
    fermeture bien mémorisée.
- **2026-07-29** — Fix du bandeau d'annonce vitrine (bandeau centré,
  **remplacé par un popup dans l'entrée ci-dessus** le même jour — gardé
  ici pour l'historique) : mise en page initiale jugée mal
  adaptée (constatée par capture d'écran utilisateur) — titre + message +
  bouton fermer tous alignés à gauche avec `margin-left:auto` sur le
  bouton, laissant un grand vide à droite sur écran large, look
  "à moitié vide" peu soigné. Corrigé : contenu (icône 📢 + titre + séparateur
  `·` + message) centré dans un conteneur `max-width:1100px` (même largeur
  que le reste du contenu vitrine), bouton fermer en `position:absolute`
  ancré au bord droit (`right:12px`, centré verticalement) au lieu de
  pousser par flex — reste à distance fixe du bord quel que soit la
  longueur du texte, jamais de grand vide. Revérifié en réel (capture
  desktop 1400px + mobile 390px, recette Playwright déjà documentée
  ci-dessous) : rendu centré correct dans les deux cas, bouton fermer
  jamais chevauché par le texte.
- **2026-07-29** — Fix visuel des graphiques `/admin` (entrée précédente,
  livrés le même jour) : en usage réel, les 6 colonnes mensuelles de
  "Réservations par mois"/"Nouveaux établissements par mois" s'empilaient
  **verticalement** au lieu de côte à côte (constaté par capture d'écran
  utilisateur), alors que le CSS semblait correct à la lecture
  (`display:flex` sur le conteneur, `flex:1` sur chaque colonne). Cause
  exacte non identifiée avec certitude (piste : hauteur en `%` imbriquée
  dans une colonne flex sans hauteur propre définie) — plutôt que de
  continuer à deviner sans navigateur, **vérifié pour de vrai** : app
  lancée localement (WAMP déjà actif), session admin simulée via un JWT
  miniature signé directement en PHP CLI (`AuthService::encode()`, écrit
  dans `localStorage` par un script d'init Playwright — évite de toucher
  au mot de passe superadmin réel) + `matchMedia('(display-mode:
  standalone)')` forcé à `true` (sinon `adminLayout()` redirige vers
  `/login`, cf. section Authentification), page `/admin` chargée et
  capturée à l'écran (`chromium` headless via `npx playwright`, pas
  disponible autrement dans cet environnement). Root-cause confirmée visuellement,
  puis **corrigé en réécrivant la grille de colonnes en CSS Grid explicite**
  (`grid-template-columns: repeat(6, 1fr)`, classes `.chart-cols`/
  `.chart-col`/`.chart-col-track` dans `admin.css`) au lieu de flexbox
  imbriqué — élimine toute ambiguïté row/column. Recapturé après fix :
  les 6 mois s'affichent bien côte à côte, et le bouton "Voir en tableau"
  testé (clic simulé) bascule correctement vers le tableau. **Piège
  d'outillage rencontré en testant** : le jeton JWT généré en pipant la
  sortie d'un `php -r` dans une variable bash se faisait polluer par
  l'avertissement `Warning: Module "mysqli" is already loaded` écrit sur
  stdout (pas stderr, donc `2>/dev/null` ne le filtrait pas) — un retour à
  la ligne au milieu du header `Authorization` cassait la requête HTTP
  (400 Bad Request côté Apache, avant même PHP). Fix : `-d
  display_errors=0` sur la commande CLI ponctuelle. **Réflexe à garder** :
  quand un rendu visuel semble correct sur le papier mais ne l'est pas en
  usage réel, vérifier dans un vrai navigateur avant de re-deviner en
  boucle — ce projet n'a pas de skill `/run` dédié pour ce faire, la
  recette ci-dessus (JWT direct + `matchMedia` forcé) peut être réutilisée
  telle quelle pour tout futur test admin/saas sans navigateur.
- **2026-07-29** — Graphiques ajoutés à `/admin` (Vue d'ensemble), suite à la
  demande explicite de diagrammes. Suivi de la méthode du skill `dataviz`
  (palette validée par script, pas à l'œil — voir `references/palette.md`
  du skill).
  - **Répartition par plan** : les 3 nombres bruts (Starter/Pro/Business)
    sont désormais accompagnés d'une mini barre de progression par ligne.
    Palette dédiée `#2a78d6`/`#eb6834`/`#1baf7a` (bleu/orange/aqua) —
    **volontairement différente** des couleurs indigo/bleu déjà utilisées
    ailleurs pour les badges de plan (`#2563EB`/`#6366F1`, cf.
    `.admin-plan-pill` dans `admin.css`) : validées via
    `node scripts/validate_palette.js`, ce doublet bleu/indigo échoue le
    contrôle CVD (ΔE 1.9, quasi indiscernable en daltonisme) — inutilisable
    pour un graphique, même s'il reste correct comme couleur de badge isolée
    (une seule couleur à la fois, pas de comparaison série à série).
  - **Deux nouveaux graphiques en colonnes** (6 derniers mois, zéro-remplis
    pour les mois sans activité) : "Réservations par mois" et "Nouveaux
    établissements par mois". `AdminController::monthlySeries()` (nouveau,
    privé) — **piège rencontré et corrigé** : la première version utilisait
    `strtotime("-N months")` depuis le jour courant, qui déborde sur le mois
    suivant quand le jour courant (29/30/31) n'existe pas dans le mois cible
    (ex. depuis un 29/30/31, -5 mois tombe sur un février qui n'a que 28/29
    jours → PHP rallonge au lieu de clamper) : un mois dupliqué, un autre
    jamais généré. Fix : partir du 1er jour du mois courant
    (`new DateTime('first day of this month')`) avant de soustraire —
    plus aucun débordement possible. Détecté par test direct contre la
    vraie base (pas juste `php -l`), à garder en tête pour toute future
    logique de plage de mois glissante.
  - Chaque graphique en colonnes a un bouton "Voir en tableau" (bascule vers
    un `<table>` classique) — la déclinaison accessible systématique
    qu'exige le skill, plutôt qu'un survol comme seul accès à la valeur (les
    valeurs sont de toute façon déjà en étiquette directe au-dessus de
    chaque barre).
  - **Piège de mise en page évité** : la hauteur de la barre (0–100%) doit
    se résoudre contre une piste de hauteur fixe dédiée (`height:90px`), pas
    contre toute la colonne flex qui contient aussi l'étiquette valeur et le
    libellé du mois — sinon une barre à 100% déborde du conteneur (débordement
    visuel constaté puis corrigé avant livraison).
  - Non testé en navigateur réel (pas d'accès depuis cet environnement) —
    validé par `php -l`, un test direct de `monthlySeries()` contre la vraie
    base (6 mois consécutifs distincts, plus de doublon) et une relecture
    manuelle de la mise en page contre les anti-patterns du skill dataviz.
- **2026-07-29** — Espace admin étoffé : 4 nouveaux outils de gestion
  (annonce/notification, messages de contact, newsletter, annonces vitrine)
  + un fix de cohérence visuelle. Nouvelle référence
  `scripts/migration_admin_tools.sql` (appliquée en local, schema.sql +
  wipe-db.sql mis à jour et revalidés comme d'habitude — clone réel +
  chargement base vierge, diffs vérifiés).
  - **Cohérence visuelle admin** (audit préalable) : `admin/layout.php` ne
    chargeait jamais `saas-modals.css` (contrairement à `saas/layout.php`)
    — les classes `.modal-card`/`.modal-list-item`/etc. n'avaient donc aucun
    effet côté admin. Ajouté. Également corrigé `.action-btn-success`
    (bouton "Réactiver" de `/admin/establishments`) : cette classe n'existe
    que dans `saas-bookings.css`, jamais chargée sur l'admin — redéfinie
    dans `admin.css` (toujours chargé). Confirmé au passage que "suspendre/
    réactiver un établissement" (`is_active`, `PUT /api/establishments/{id}`)
    existait déjà et fonctionnait de bout en bout pour un superadmin
    (`Guard::canAccessEstablishment()` bypass déjà en place) — seul le bug
    CSS empêchait de le voir clairement.
  - **Notification "annonce plateforme"** (`/admin/notifications`) :
    `NotificationService::broadcastToOwners()` (nouveau) envoie à tous les
    `users` de rôle `owner` (pas les receptionists), type
    `platform_announcement` (icône 📢 ajoutée à `typeConfig()` dans
    `saas.js`, composant partagé saas+admin). `AdminController::
    broadcastNotification()` (`POST /api/admin/notifications/broadcast`).
    Pas d'historique de campagnes conservé (contrairement à la newsletter
    ci-dessous) — volontairement simple, un formulaire titre+message sans
    tracking d'envois passés.
  - **Messages de contact** (`/admin/contact-messages`) : le formulaire
    `/contact` n'envoyait qu'un email (`MailService::sendContact()`),
    jamais persisté — aucune trace consultable. Nouvelle table
    `contact_messages`, `Models\ContactMessage`, `AdminContactController`
    (index/markRead/destroy). **Ordre important dans
    `PublicController::sendContact()`** : le message est désormais stocké
    EN BASE avant toute tentative d'email (source de vérité), l'email
    devient un canal best-effort en plus (`try/catch` qui n'échoue plus la
    requête) — un SMTP en panne ne fait plus perdre le message du visiteur.
  - **Newsletter** (`/admin/newsletter` + formulaire footer vitrine) :
    tables `newsletter_subscribers` (jeton `unsubscribe_token` opaque,
    même principe que `bookings.guest_token`) et `newsletter_campaigns`
    (historique, `recipient_count`). `PublicController::
    newsletterSubscribe()`/`newsletterUnsubscribe()`, `AdminNewsletterController`
    (subscribers/campaigns/send). **Envoi synchrone** au clic "Envoyer" côté
    admin (`MailService::newsletterCampaign()`, boucle sur tous les
    abonnés actifs) — même limite assumée que `Services\BackupService` :
    pas de file d'attente, à revoir si la liste grossit significativement.
    Lien de désabonnement → `/newsletter/desabonnement?token=...`
    (nouvelle page vitrine autonome, `vitrine/newsletter-unsubscribe.php`,
    ajoutée à la détection `$pageName` de `vitrine/layout.php`).
    Formulaire d'inscription dans le footer (`newsletterFooterForm()` dans
    `vitrine.js`, colonne "Brand" du footer — pas de 5ᵉ colonne pour ne pas
    toucher le grid CSS à 4 colonnes existant).
  - **Annonces vitrine** (`/admin/announcements`) : table `announcements`,
    `Models\Announcement`, `AdminAnnouncementController`. **Une seule
    active à la fois PAR CONSTRUCTION** : `store()`/`update()` désactivent
    systématiquement toutes les autres lignes avant d'en activer une
    nouvelle (pas juste "la plus récente gagne") — évite que deux lignes
    affichent "Active" simultanément dans le tableau admin alors qu'une
    seule s'affiche réellement sur la vitrine.
    - **Bandeau vitrine** (`vitrine/layout.php`) : la nav flottante
      (`vitrineNav()`) est `position:fixed` en mode "pilule" permanent
      (cf. journal 2026-07-19) — un bandeau ajouté naïvement au-dessus se
      serait superposé à elle. Solution : `Alpine.store('vitrineChrome',
      { bannerHeight })` partagé entre le composant bandeau
      (`vitrineAnnouncementBanner()`, mesure sa propre hauteur réelle via
      `$el.offsetHeight`, jamais une valeur devinée) et la nav, qui décale
      son `top` fixe de cette valeur. Fermeture mémorisée par ID d'annonce
      (`localStorage`), pas globalement — une nouvelle annonce publiée
      plus tard réapparaît même si l'ancienne avait été fermée.
  - **Non testé en navigateur réel** dans cette session (pas d'accès
    navigateur depuis cet environnement) — validé par `php -l`/`node
    --check`, un smoke test runtime complet (broadcast, contact message,
    annonce active, abonnement/désabonnement newsletter, contre la vraie
    base puis nettoyé) et les validations habituelles de schema.sql/
    wipe-db.sql. À vérifier manuellement en priorité : le calage du
    bandeau d'annonce avec la nav flottante sur mobile (largeur de texte
    variable → hauteur mesurée différente), et l'envoi réel d'un email de
    campagne newsletter (SMTP non testé dans cet environnement).
- **2026-07-29** — Changement de palier de récompense agent : **5 premiers-
  abonnements** (au lieu de 10) pour déclencher un versement, montant réduit
  en proportion pour garder le même taux par abonnement (3 000 F/abonnement
  Pro, 6 000 F/abonnement Business, inchangé) :
  - `config/plans.php` : clé renommée `agent_reward_per_10` →
    `agent_reward_per_5`, valeurs 30 000→**15 000** (Pro) et
    60 000→**30 000** (Business).
  - `CommissionService::BATCH_SIZE` : 10 → 5, et **rendue `public`** (au lieu
    de `private`) pour être réutilisée telle quelle par
    `AgentController::me()` (`progress[plan].target`) plutôt que de
    dupliquer le chiffre dans deux fichiers — même classe de risque de
    dérive que celle rencontrée le jour même sur `schema.sql`/`wipe-db.sql`.
  - `src/templates/agent/dashboard.php` : barre de progression et libellés
    ne codent plus "10" en dur, lisent désormais `progress[plan].target`
    (valeur dynamique venant de l'API) — un futur changement de palier
    n'impliquera donc plus qu'une seule modification (`CommissionService::
    BATCH_SIZE` + `config/plans.php`), plus aucun texte de template à
    modifier.
  - Textes marketing mis à jour : `agent/register.php` ("tous les 5
    abonnements") et `admin/agents.php` ("palier de 5 abonnements").
  - **Rétrocompatible sans migration** : les lignes `agent_referrals`
    déjà en attente (`payout_id IS NULL`), y compris celle backfillée plus
    tôt dans la journée pour l'établissement #1, sont automatiquement
    comptées sous la nouvelle règle dès le prochain scan/upgrade qui
    déclenche `CommissionService::maybeCreatePayout()` — pas de colonne
    "créé sous l'ancienne règle" à gérer, le calcul est toujours fait à la
    volée sur `COUNT(payout_id IS NULL)`.
  - Validé par un test runtime direct (`CommissionService::BATCH_SIZE` et
    `config/plans.php` relus via PHP CLI) : `BATCH_SIZE=5`, récompenses
    15000/30000 correctement résolues.
- **2026-07-29** — `scripts/wipe-db.sql` remis à niveau : 5 tables manquantes
  ajoutées (`agents`, `agent_establishments`, `agent_referrals`,
  `agent_payouts`, `user_sessions`) — aucune n'était vidée depuis leur
  introduction (agents : 2026-07-27 ; user_sessions : 2026-07-26), même
  angle mort déjà rencontré le 2026-07-18 (9 tables manquantes à l'époque).
  DELETE placés avant `establishments`/`agents`/`users` selon les FK
  (`agent_referrals`/`agent_establishments` → agents+establishments CASCADE,
  `agent_payouts` → agents CASCADE, `user_sessions` → users CASCADE) ;
  `AUTO_INCREMENT` reseté pour les 5 (PK auto_increment sur toutes).
  Validé en conditions réelles : clone de `hotel_sync` (mysqldump) dans une
  base jetable, `wipe-db.sql` exécuté dessus, `SELECT COUNT(*)` = 0 sur les
  5 tables + les tables existantes (les compteurs `information_schema.tables`
  affichés juste après restent des estimations InnoDB périmées, à ignorer —
  seul `COUNT(*)` fait foi juste après un DELETE).
  - **Trouvé au passage, corrigé aussi** : `scripts/schema.sql` (snapshot
    consolidé pour base vierge) n'avait **jamais** eu la table
    `user_sessions` ni la colonne `users.avatar_path` ajoutées
    (`migration_account_settings.sql`, 2026-07-26, jamais répercutée dans le
    snapshot — contrairement à `agents`/etc. qui y étaient déjà). Une
    install fraîche à partir de `schema.sql` aurait donc manqué la
    fonctionnalité "Appareils connectés" de `/saas/settings`. Ajouté
    (colonne + table, avec sa FK CASCADE vers `users`), validé par un load
    complet dans une base jetable et comparaison `SHOW TABLES` avec la base
    réelle (identique).
  - **Piège à retenir** (déjà noté le 2026-07-20 mais reconfirmé ici) :
    `schema.sql`/`wipe-db.sql` ne sont mis à jour par aucun mécanisme
    automatique — chaque nouvelle table doit être ajoutée à la main aux
    deux fichiers, sinon la dérive est invisible tant que personne ne fait
    d'install fraîche ou de wipe complet.
- **2026-07-29** — Nouvelle page **"Mon profil"** dans l'espace agents
  commerciaux (`/agent/profile`), absente jusqu'ici (seul `/agent/dashboard`
  existait). Édition nom/numéro/opérateur Mobile Money + changement de mot
  de passe — même besoin que l'onglet Compte de `/saas/settings`, mais
  l'espace agent n'a pas de layout partagé (`Response::render()` ne wrappe
  que les préfixes `saas/`/`admin/`/`vitrine/` — `agent/*` reste toujours
  autonome, cf. section Architecture technique), donc chaque page agent est
  un document HTML complet indépendant.
  - `AgentController::updateProfile()` (`PUT /api/agent/profile`) : valide
    nom/numéro (`isValidCiPhone()`, déjà utilisée par `register()`) et
    l'opérateur, vérifie l'unicité du numéro (identifiant de connexion,
    `agents.numero` UNIQUE) hors le compte courant. **Point d'attention** :
    `numero` sert aussi de `mobile_money_number` au moment de la création
    d'un versement (`CommissionService::maybeCreatePayout()`) — un
    changement de numéro ne modifie que les futurs versements, les
    `agent_payouts` déjà créés gardent leur numéro figé au moment de leur
    création (comportement normal, pas un bug).
  - `AgentController::changePassword()` (`POST /api/agent/change-password`) :
    même règles que `AuthController::changePassword()` (min. 8 caractères,
    lettre+chiffre, rate-limit 5/h).
  - `PageController::agentProfile()` + route `GET /agent/profile`.
  - `src/templates/agent/profile.php` (nouveau) : même tête HTML que
    `dashboard.php` (manifest/icônes agent dédiés). `agent-dashboard.css`
    étendu (pas de nouveau fichier CSS) : `.ag-tabs`/`.ag-tab` (nav
    Tableau de bord / Mon profil, ajoutée aussi en haut de `dashboard.php`
    qui n'avait aucune navigation avant), `.ag-card`/`.ag-form-*` (styles de
    formulaire, absents jusqu'ici de ce fichier).
  - `public/assets/js/pages/agent-profile.js` (nouveau) : composant Alpine
    `agentProfilePage()`, même structure que `agentDashboardPage()`
    (token dans `localStorage['agent_token']`, cache `localStorage['agent']`).
  - Non testé en navigateur réel dans cette session — vérifié par `php -l`
    (fichiers PHP) et `node --check` (JS) uniquement.
- **2026-07-29** — Resynchronisation complète `src`/`public`/`config`/`scripts`
  ↔ `copy/` (`diff -rq` vide sur les quatre arborescences, hors
  `public/assets/uploads/*` qui reste volontairement exclu — données
  runtime, même règle que `storage/backups/`). Deux dérives préexistantes
  (antérieures à cette session, jamais liées au travail du jour) trouvées et
  corrigées au passage : `public/assets/js/anti-inspect.js` (protection
  anti-inspection déjà désactivée côté `src`/`public` mais `copy/` gardait
  l'ancienne version active) et `public/manifest-agent.webmanifest` (tiret
  cadratin retiré de la description côté `src`, pas répercuté dans `copy/`).
- **2026-07-29** — QR code établissement désactivé visuellement une fois
  rattaché à un agent (`/saas/settings` → "Mon QR code"). Avant ce fix, le
  modal affichait toujours le QR comme actif même après un premier scan
  réussi — rien ne signalait qu'un rattachement existait déjà, alors que le
  serveur refuse silencieusement (409/idempotent) tout nouveau scan
  (`agent_establishments.establishment_id` UNIQUE, premier scan gagne,
  jamais de réassignation — cf. section "Espace agents commerciaux").
  - `EstablishController::qr()` (`GET /api/establishment/qr`) renvoie
    désormais aussi `agent_linked` (bool, via
    `AgentEstablishment::findByEstablishment()`).
  - `saas-settings.js::openMyQrCode()` : nouvel état `qrAgentLinked`. Si
    vrai, ne génère plus le QR canvas.
  - `settings.php` : modal "Mon QR code" affiche un message "QR code
    désactivé — déjà rattaché à un agent commercial" à la place du code
    quand `qrAgentLinked` est vrai.
  - Aucun changement côté `AgentController::scanQr()` : la protection
    serveur (refus de réassignation) existait déjà, ce fix ne couvre que
    l'affichage propriétaire qui ne la reflétait pas.
- **2026-07-29** — Changement de règle métier sur la récompense agent
  (section "Espace agents commerciaux" plus haut) : un établissement **déjà**
  sur un plan payant (pro/business) au moment où l'agent scanne son QR
  compte désormais immédiatement dans la progression 0/10, au lieu de
  n'être crédité que si l'upgrade a lieu *après* le rattachement.
  - Repéré via un cas réel : établissement "yao yoann" (id 1) passé en
    Business à 14:05:46, scanné par l'agent à 15:15:30 (même jour) — le
    dashboard affichait "1 établissement rattaché" mais 0/10, car
    `CommissionService::recordFirstSubscription()` n'était appelée que
    depuis `SubscriptionController` (upgrade), qui exige que le lien agent
    existe déjà et que le plan précédent soit `starter` — un établissement
    déjà payant au moment du scan ne déclenchait donc jamais de crédit.
    Décision produit assumée : l'ancien comportement (anti-fraude, éviter
    qu'un agent scanne un client déjà payant pour gonfler son compteur) est
    volontairement abandonné au profit d'un crédit immédiat au scan.
  - Fix : `AgentController::scanQr()` appelle désormais
    `CommissionService::recordFirstSubscription((int)$estab['id'],
    $estab['plan'])` juste après `AgentEstablishment::create()`, avec le
    plan **courant** de l'établissement (pas nécessairement issu d'un
    upgrade). Aucun changement dans `CommissionService` lui-même — la
    fonction était déjà idempotente (`AgentReferral::findByEstablishment()`
    empêche tout double comptage), donc un upgrade/renouvellement ultérieur
    du même établissement ne recrédite pas une seconde fois. Docblock mis à
    jour pour documenter les deux points d'appel.
  - **Backfill ponctuel** : ligne `agent_referrals` créée manuellement en
    base pour l'établissement #1 (via un appel direct à
    `CommissionService::recordFirstSubscription(1, 'business')`) pour
    refléter rétroactivement le nouveau comportement sur ce cas déjà scanné
    — pas un mécanisme automatique, à refaire à la main si d'autres
    établissements pré-existants sont dans le même cas.
- **2026-07-29** — Fix scan QR agent (`/agent/dashboard`) : la caméra ne
  s'ouvrait jamais (repli silencieux sur la saisie manuelle du code). Cause :
  l'en-tête `Permissions-Policy` global de `public/index.php`
  (appliqué à toutes les réponses, ajouté avant l'espace agents du
  2026-07-27) fixait `camera=()` — interdiction de la caméra sur **toute**
  origine, y compris le même site. `agent-dashboard.js::openScanner()`
  (`getUserMedia`) échouait donc systématiquement, quel que soit le
  navigateur/appareil. Fix : `camera=(self)` (même principe que
  `geolocation=(self)` déjà en place pour `saas-settings.js::locateMe()`).
  microphone reste bloqué (`microphone=()`), inutilisé. **Piège à retenir** :
  toute nouvelle fonctionnalité utilisant une permission navigateur
  (caméra, micro, géoloc...) doit vérifier `Permissions-Policy` dans
  `public/index.php`, pas seulement le code JS qui l'appelle — l'échec est
  silencieux côté navigateur (pas d'erreur console explicite reliant les
  deux), seul `getUserMedia()` qui rejette le laisse deviner.
- **2026-07-29** — Retrait complet du module "mode hors ligne SaaS" (Partie A
  de `todo_list.md`, tranche 1 implémentée le 2026-07-23 — voir entrées
  correspondantes plus bas, conservées pour l'historique mais **le code
  qu'elles décrivent n'existe plus**). Décision produit : périmètre jugé trop
  limité (check-in/check-out seulement) pour la complexité ajoutée, et aucune
  suite (A4 idempotence, A5/A6) n'était prévue à court terme.
  - Supprimé : `public/assets/js/offline-db.js` (wrapper IndexedDB en entier).
  - `saas.js` : état `isOnline`/`outboxPending`/`syncing`, listeners
    `online`/`offline`, ping périodique 15s, `refreshOutboxPending()`,
    `handleReconnect()`, le helper `saasHelpers.offlineGuard()`, et le garde
    `navigator.onLine` dans `notificationsPanel().load()` — tous retirés.
    Les blocs `try/catch` autour des `fetch()` de `init()` et
    `loadPendingBookingsCount()` redeviennent de simples gestions d'erreur
    réseau génériques (comme avant le 2026-07-23).
  - `saas-bookings.js` : write-through cache (`AfristayOffline.cacheBookings`),
    lecture de secours (`getCachedBookings`, `usingCachedData`), mise en file
    `queueBookingAction` + badge `pendingSync` dans `updateStatus()`, appels
    `offlineGuard()` (`loadBookings`/`loadRooms`/`loadClientsList`), listener
    `afristay:outbox-flushed` — tous retirés. `loadBookings()` retombe sur un
    simple message d'erreur réseau en cas d'échec.
  - `saas-planning.js`/`saas-rooms.js` : appels `offlineGuard()` retirés
    (3 endroits).
  - `saas/layout.php` : `<script src=".../offline-db.js">` retiré, bandeau
    "Vous êtes hors ligne" (`.saas-offline-banner`) supprimé, bandeau email
    non vérifié repassé en `x-show="showEmailVerifyBanner"` (sans la
    condition `isOnline` devenue inutile).
  - `bookings.php` : badge "⏳ En attente"/"En attente de synchro"
    (`pendingSync`) retiré (liste + modal détail), bloc "Données hors ligne"
    (`usingCachedData`) retiré.
  - `saas-responsive.css` : règles `.saas-offline-banner` retirées.
  - **Conservé volontairement** : la garde de précondition de statut dans
    `BookingController::checkIn()/checkOut()` (409 si le booking n'est plus
    `confirmed`/`checked_in`) — protège aussi le double-clic en ligne,
    indépendamment de tout mécanisme hors ligne ; commentaire nettoyé de sa
    référence à l'outbox. Également conservés les correctifs de `public/sw.js`
    (bump `afristay-v8`, `OFFLINE_HTML`/`LOGIN_URL` en comparaison exacte,
    `Response.error()` au lieu de `throw`) : ce sont des corrections de bugs
    du Service Worker minimal préexistant (shell PWA), pas des ajouts du
    module retiré — les garder évite de réintroduire le bug où une page SaaS
    jamais mise en cache affichait à tort le formulaire de connexion lors
    d'une navigation hors ligne.
  - `todo_list.md` mis à jour en conséquence (Partie A repassée à l'état de
    plan, rien d'implémenté).
  - Resynchronisé dans `copy/` (diff vérifié vide sur tous les fichiers
    touchés) ; validé par `php -l` (fichiers PHP) et `node --check` (fichiers
    JS) uniquement — pas de test navigateur dans cette session.
- **2026-07-27** — Espace agents commerciaux (fonctionnalité temporaire,
  démarchage terrain d'établissements). Verrou global **`AGENTS_ENABLED`**
  (`config/config.php`, défaut `true`) sur le même principe que
  `ONLINE_PAYMENTS_ENABLED` — coupe `/agent/*` et le bouton "Mon QR code" sans
  supprimer le code.
  - **Les agents ne sont PAS des `users`** — pas d'établissement, connexion
    par numéro de téléphone (`AuthController::login()` ne cherche que par
    email). Entité séparée, table `agents` (nom, numéro unique,
    opérateur_money, password_hash), aucune modif de `Guard.php` ni de
    l'enum `users.role`. JWT émis via `AuthService::encode()` avec un
    payload `role: 'agent', agent_id: ...` **sans clé `id`** : évite
    `recordSession()` (FK implicite vers `users.id`) tout en réutilisant
    blacklist/expiration JWT. `Core\Middleware::checkRole()` ne lit que
    `$user['role']`, donc `role:agent` fonctionne sans modif.
  - **Rattachement établissement ↔ agent** : chaque établissement a un
    `qr_token` unique (`establishments.qr_token`, généré automatiquement
    dans `Establishment::create()`), affiché en QR sous le bouton
    "Mon QR code" de `/saas/settings` (onglet Général). L'agent le scanne
    depuis `/agent/dashboard` (caméra + jsQR vendorisé) →
    `POST /api/agent/scan` → ligne dans `agent_establishments`
    (`establishment_id` UNIQUE : premier scan gagne, verrouillé ensuite,
    pas de réassignation automatique).
  - **Récompense — PAS un montant par établissement** : forfait par palier
    de 10 premiers-abonnements d'un même plan (30 000 F/10 Pro, 60 000 F/10
    Business, `config/plans.php[plan]['agent_reward_per_10']`). Table
    `agent_referrals` (une ligne par premier-abonnement qualifiant,
    `establishment_id` UNIQUE, `payout_id` NULL tant que le palier n'est pas
    atteint) + `agent_payouts` (même schéma que `payout_requests`, traité
    manuellement par un superadmin sur `/admin/agents`). Logique dans
    `Services\CommissionService::recordFirstSubscription()`, appelée depuis
    les 3 points de `SubscriptionController` où
    `UPDATE establishments SET plan=...` a lieu, uniquement si le plan
    précédent était `starter` (pas les renouvellements).
  - QR généré/décodé côté client, deux libs MIT vendorisées (pas de build,
    même logique que `public/assets/vendor/leaflet`) :
    `qrcode-generator` (Kazuhiko Arase) et `jsQR` (cozmo).
- **2026-07-26** — Onglet Compte de `/saas/settings` jugé trop encombré (mélangeait
  identité, notifications et sécurité) et pas assez fonctionnel (aucune édition
  de profil possible). Refonte :
  - **Notifications extraites dans un onglet dédié** (`activeTab==='notifications'`,
    visible à tous les rôles comme Compte) : le toggle push et la liste des
    types de notification déménagent tels quels, Compte ne garde plus que
    identité/sécurité.
  - **Édition de profil** (nom, téléphone) — n'existait pas du tout avant :
    `AuthController::updateProfile()` (`PUT /api/auth/profile`).
  - **Photo de profil** : `users.avatar_path` (nouvelle colonne),
    `UploadService::uploadAvatar()` (même pipeline de compression que les
    photos établissement/chambre, 512px max), `AuthController::uploadAvatar()`/
    `deleteAvatar()` (`POST`/`DELETE /api/auth/avatar`).
  - **Appareils connectés (sessions + historique de connexion)** — le JWT était
    jusqu'ici purement stateless (seule une liste noire `token_blacklist`
    existait, cf. section Authentification). Nouvelle table `user_sessions`
    (jti, ip, user-agent, device_label parsé heuristiquement, expires_at,
    revoked_at), alimentée directement dans `AuthService::encode()` (chaque
    jeton émis par login/register = une ligne) et mise à jour dans
    `AuthService::revoke()`. Sert à la fois de liste des sessions actives
    (révocables individuellement ou en bloc via `POST /api/auth/sessions/
    {id}/revoke` et `/revoke-others`) et d'historique de connexion (lignes
    expirées/révoquées gardées 90 jours, purge occasionnelle comme
    `token_blacklist`). Vérification d'ownership stricte sur `revokeSession`
    (cf. pattern IDOR déjà documenté) — testé explicitement avec deux comptes
    distincts.
  - Migration : `scripts/migration_account_settings.sql` (déjà appliquée en
    local). Changement de mot de passe et suppression de compte (mailto
    support) inchangés.

- **2026-07-26** — Nouvelle fonctionnalité : le voyageur (rôle `client`, sans
  compte) peut désormais télécharger sa réservation en PDF et la retrouver
  plus tard sans consulter son email de confirmation. Aucun système de
  compte/mot de passe voyageur ajouté — tout repose sur le `guest_token`
  déjà existant (`bookings.guest_token`, cf. section Authentification) et un
  historique local navigateur.
  - **`PdfService::generateBookingConfirmation()`** (nouveau, même charte
    graphique FPDF que `generateInvoice()`) : régénéré à chaque
    téléchargement (jamais mis en cache sur disque comme les factures, car
    le statut pending/confirmed/annulée peut changer entre deux téléchargements).
  - **`PublicController`** (3 nouvelles méthodes, toutes publiques
    `/api/public/booking/*`) :
    - `bookingShow`/`bookingPdf` (`GET .../booking/{id}` et `.../booking/{id}/pdf`,
      `?token=guest_token`) : preuve de propriété par `hash_equals()` sur le
      `guest_token`, même mécanique que `PushController::subscribeGuest()`.
    - `bookingFind` (`POST /api/public/booking/find`, rate-limité comme
      `bookingRequest`) : "Retrouver ma réservation" par email+téléphone —
      **pas** de code envoyé par email (c'est justement le canal que ce
      voyageur ne consulte pas). `PublicClient::findByEmailAndPhone()`
      (nouveau) normalise le téléphone (chiffres seuls, indicatif 225 retiré,
      10 derniers chiffres) pour tolérer les variantes de saisie. Message
      d'erreur générique si aucune correspondance (ne confirme jamais qu'un
      email existe en base).
    - `Booking::publicHistoryForClient()` (nouveau) renvoie le `guest_token`
      de chaque réservation trouvée — l'identité ayant déjà été prouvée par
      `bookingFind`, ce jeton permet ensuite le téléchargement PDF sans
      re-authentification.
    - `Booking::findWithDetails()` étendu avec `establishment_phone`/
      `establishment_address`/`establishment_city` (additif, sans impact sur
      les consommateurs existants : BookingController, BookingPaymentController).
  - **`/mes-reservations`** (nouvelle page vitrine publique,
    `PageController::myBookings`) : au chargement, relit l'historique local
    de l'appareil (`AfristayMyBookings`, localStorage) et rafraîchit le
    statut de chaque réservation via `bookingShow` ; sinon affiche le
    formulaire de recherche email+téléphone (`bookingFind`). Lien ajouté au
    footer vitrine (pas à la nav principale desktop/mobile, réservée aux
    prospects/hôtes — jugé plus cohérent en navigation secondaire).
  - **`public/assets/js/my-bookings-store.js`** (nouveau, chargé globalement
    dans `vitrine/layout.php` avant `vitrine.js`) : petit wrapper localStorage
    partagé (`window.AfristayMyBookings.add/list`) — écrit par `booking.js` à
    la confirmation (paiement sur place ET vérification paiement en ligne),
    lu par `mes-reservations.js`. Ne stocke que `{id, guest_token}`, jamais
    les détails (relus à chaque affichage, peuvent changer côté hôtel).
  - **Téléchargement en lien direct, pas fetch+blob** : contrairement aux PDF
    SaaS (`saas-billing.js`) qui nécessitent fetch+blob à cause du Bearer
    token en header, ici l'auth passe par `guest_token` en query string —
    un simple `<a href="...">` suffit, le navigateur télécharge directement.
  - Écran de confirmation (`vitrine/booking.php`) : bouton "Télécharger ma
    réservation (PDF)" + lien vers `/mes-reservations`, pour le voyageur qui
    ne consulte pas ses emails dès l'instant de la réservation.
  - Non testé en navigateur réel dans cette session (pas d'accès navigateur
    depuis cet environnement) — vérifié par `php -l` (tous les fichiers PHP
    touchés) et `node --check` (tous les fichiers JS touchés) uniquement. À
    valider manuellement : tunnel de réservation complet (sur place + retour
    paiement en ligne), téléchargement PDF, recherche email+téléphone avec
    variantes de saisie du numéro (`+225 01...`, `01 23 45...`), rendu du PDF
    (encodage ISO-8859-1 des caractères accentués, cf. piège déjà connu sur
    `generateInvoice`).

- **2026-07-23** — Fix `public/sw.js` (`CACHE` bumpé en `afristay-v8`) : bug
  découvert en testant la tranche 1 du mode hors ligne (voir entrée
  suivante) — le repli de navigation hors ligne servait la page `login` en
  cache pour **n'importe quelle** page jamais visitée (`req.mode ===
  'navigate'`, aucun match cache), pas seulement pour `/login` elle-même.
  Un owner déjà connecté qui naviguait hors ligne vers une page SaaS pas
  encore mise en cache (ex. après le login, `window.location.href =
  '/saas'` dans `login.js:110-112`, puis clic sur un lien sidebar vers une
  page jamais visitée cette session — navigation classique multi-pages, pas
  de SPA) se retrouvait avec le formulaire de connexion réaffiché à la
  place, alors que son token en localStorage n'avait pas bougé — perçu à
  tort comme une déconnexion automatique. Fix : le fallback vers la page
  `login` en cache ne s'applique plus qu'aux navigations dont l'URL cible
  est effectivement `/login` (comparaison exacte via `LOGIN_URL`) ; toute
  autre navigation hors ligne non mise en cache reçoit désormais une page
  neutre générée inline (`OFFLINE_HTML`, sans formulaire) invitant à
  réessayer une fois reconnecté. **Se connecter hors ligne reste par
  ailleurs impossible et volontairement hors scope** : `/api/auth/login`
  nécessite le réseau, aucune tranche du plan `todo_list.md` (Partie A) ne
  prévoit d'authentification hors ligne.
  Deuxième fix découvert dans la foulée (même fichier, même session) : le
  bloc `catch` du handler `fetch` finissait par `throw e;` pour toute
  requête GET échouée sans correspondance en cache (hors le cas navigation
  ci-dessus) — ce qui fait **rejeter** la promesse passée à
  `event.respondWith()`. Chrome logue alors un "Uncaught (in promise) /
  FetchEvent resulted in a network error response" en console à **chaque**
  échec, ce qui devenait très bruyant avec le ping périodique 15s ajouté
  dans `saas.js` (tranche mode hors ligne, entrée suivante) tant que l'app
  reste hors ligne. Fix : `return Response.error();` au lieu de `throw e;`
  — résout la promesse avec une Response de type erreur réseau plutôt que
  de la rejeter ; le comportement côté page (le `fetch()` appelant échoue
  toujours avec un `TypeError`, `.catch()` se déclenche pareil) est
  inchangé, seul le bruit console côté Service Worker disparaît. **Piège
  à retenir** : dans un handler `fetch` de Service Worker, ne jamais
  `throw`/rejeter à l'intérieur de la fonction passée à `respondWith()` —
  toujours résoudre, y compris pour représenter un échec (`Response.error()`).
- **2026-07-23** — Partie A de `todo_list.md` (mode hors ligne SaaS) : première
  tranche implémentée, couvrant A1 (détection + bandeau), A2 (cache lecture) et
  A3 (outbox), tous trois limités au strict nécessaire pour rendre le
  check-in/check-out utilisable pendant une coupure réseau. A4 (idempotence
  générique via `client_uuid`) et A6 (Background Sync API) restent non
  implémentés — pas nécessaires pour ce périmètre car un changement de statut
  `checked_in`/`checked_out` est naturellement rejouable (simple `UPDATE`),
  contrairement à une création de réservation/paiement.
  - **`public/assets/js/offline-db.js`** (nouveau) : wrapper IndexedDB
    (`afristay-offline`, stores `bookings-cache` et `outbox`), exposé en
    `window.AfristayOffline`. Chargé dans `saas/layout.php` avant `saas.js`.
    `flushOutbox()` rejoue l'outbox dans l'ordre chronologique ; toute réponse
    serveur reçue (succès ou échec) purge l'entrée, seule une exception
    réseau interrompt le flush (le reste attend la prochaine reconnexion).
    **Piège rencontré** : `cacheBookings(estId, bookings)` plantait en
    `DataCloneError` (`store.put` sur IndexedDB) — `bookings` est le tableau
    réactif Alpine (`this.bookings`, un Proxy), non clonable par
    l'algorithme de clonage structuré d'IndexedDB. Fix : round-trip
    `JSON.parse(JSON.stringify(bookings))` avant l'écriture. Réflexe à
    garder pour tout futur store IndexedDB alimenté depuis un state Alpine.
  - **`saas.js`** (`saasLayout()`) : nouvel état `isOnline`/`outboxPending`,
    listeners `online`/`offline` + ping périodique (15s, `GET /api/auth/me`)
    tant que hors ligne (les réseaux captifs peuvent faire mentir
    `navigator.onLine`/l'event `online` dans le sens "dit en ligne alors
    que non" — jamais dans l'autre sens). Le ping ne tente donc la requête
    que si `navigator.onLine` dit vrai malgré `isOnline` encore à faux :
    sinon, une tentative réseau à chaque cycle ne fait que remplir la
    console d'échecs prévisibles (interceptés par le SW, cf. entrée
    précédente). Même correctif appliqué au poll notifications préexistant
    (`notificationsPanel()`, tournait déjà toutes les 30s sans condition
    réseau). `handleReconnect()` déclenche
    `flushOutbox()` puis un event DOM `afristay:outbox-flushed` pour que les
    pages concernées se rafraîchissent.
    **Complément (même jour)** : gater seulement les *intervalles* périodiques
    ne suffisait pas — cette app n'est pas une SPA, donc **chaque navigation
    complète** relance tous les appels initiaux (`init()`) sans condition,
    et c'est ça qui dominait le bruit console en pratique (constaté en
    testant plusieurs pages hors ligne à la suite : `auth/me`,
    `establishments`, `notifications`, `bookings`, `rooms`, `planning`,
    `room-types`, `clients` — un "FetchEvent resulted in network error
    response" par requête, cf. entrée précédente). Fix : nouveau helper
    partagé `saasHelpers.offlineGuard()` (`if (!navigator.onLine) throw new
    Error('offline')`) ajouté en première ligne de chaque bloc `try` qui
    fait un `fetch()` initial — `saasLayout().init()` (x2, auth/me +
    establishments) et `loadPendingBookingsCount()` dans `saas.js`,
    `notificationsPanel().load()`, et dans les pages déjà exercées en test :
    `saas-bookings.js` (`loadBookings`, `loadRooms`, `loadClientsList`),
    `saas-planning.js` (`loadData`), `saas-rooms.js` (`loadRooms`,
    `loadRoomTypes`). Réutilise le `catch` déjà existant de chaque fonction
    (fallback cache/tableau vide identique à un vrai échec réseau) — aucune
    logique de fallback dupliquée. **Non couvert** : les autres pages SaaS
    (billing, clients, dashboard, expenses, invoices, payments, payouts,
    reports, settings) n'ont pas ce garde-fou — pas testées hors ligne dans
    cette session, à appliquer au même endroit (première ligne du `try`) si
    le même bruit y est constaté. Les actions déclenchées par clic
    (création/modification/suppression) n'ont pas non plus ce garde-fou :
    un seul échec par clic n'est pas un problème de volume, et l'erreur
    réseau affichée reste l'information utile dans ce cas.
  - **Bandeau hors ligne** (`saas/layout.php`, CSS `.saas-offline-banner` dans
    `saas-responsive.css` — jamais dans `saas.css`, build Tailwind) : même
    technique de positionnement que `.saas-verify-banner` (compense
    sidebar/topbar fixes). Les deux bandeaux sont rendus mutuellement
    exclusifs (`x-show="isOnline && showEmailVerifyBanner"` /
    `x-show="!isOnline"`) pour éviter un empilement d'offsets.
  - **`saas-bookings.js` + `bookings.php`** : `loadBookings()` fait un cache
    write-through (`AfristayOffline.cacheBookings`) à chaque succès, et lit le
    cache en secours si le fetch échoue (`usingCachedData`, hint visuel
    "Données hors ligne"). `updateStatus()` : si l'échec réseau concerne
    spécifiquement `checked_in`/`checked_out` et que `navigator.onLine` est
    faux, l'action est mise en file (`queueBookingAction`) au lieu d'échouer
    — mise à jour optimiste + badge `pendingSync` (chip `⏳ En attente`,
    réutilise `.modal-chip` de `saas-modals.css`) sur la ligne et le modal
    détail. Les autres transitions de statut (annulation, etc.) gardent le
    comportement d'erreur existant, non couvertes par cette tranche.
  - **`BookingController::checkIn()/checkOut()`** : ajout d'une garde de
    précondition sur le statut courant (`confirmed` pour check-in,
    `checked_in` pour check-out) avant l'`UPDATE`, réponse `409` sinon.
    Protège le double-clic en ligne ET le rejeu d'une action en attente
    devenue caduque (ex. réservation annulée par un autre appareil pendant la
    coupure) — sans nécessiter de colonne d'idempotence, puisque `flushOutbox`
    purge l'entrée dès qu'une réponse serveur (même 409) est reçue.
  - **Limites assumées, non couvertes par cette tranche** : pas de
    modification de `sw.js` (le cache reste au niveau JS/IndexedDB, pas de
    couche Service Worker redondante) — donc un rechargement complet de la
    page hors ligne (F5 sans réseau) reste dégradé, seule la continuité *dans*
    une session déjà chargée est couverte. Cache lecture limité à la page
    Réservations (pas planning/chambres/clients). Aucune couverture pour la
    création de réservation manuelle ou l'encaissement hors ligne (nécessite
    A4, l'idempotence par `client_uuid`, non triviale — cf. `todo_list.md`).
  - Non testé en navigateur réel dans cette session (pas d'accès navigateur
    depuis cet environnement) — vérifié par lecture de code, lint PHP
    (`php -l`) et vérification syntaxique JS (`node --check`) uniquement.
    À valider manuellement : DevTools → Network → Offline sur `/saas/bookings`.
- **2026-07-23** — Monogramme "IS" (reste de l'ancien nom "Ivoire Stay")
  remplacé par "AS" (Afristay) partout où il apparaissait : badge carré des
  7 pages autonomes (`brand-sq`/`ck-brand-sq`) et watermarks décoratifs en
  arrière-plan (`rg-ghost`/`lg-ghost` des mêmes pages, `ab-cta-deco` dans
  `apropos.php`, `hm-hero-ghost`/`hm-cta-deco` dans `home.php`).
- **2026-07-23** — Logo "Afristay" (un seul mot, non conforme à la charte
  "Afri" + "Stay" doré utilisée partout ailleurs) corrigé dans les 7 pages
  autonomes (`checkout`, `forgot-password`, `install`, `login`, `register`,
  `reset-password`, `verify-email`) : `<strong>Afristay</strong>` →
  `<strong>Afri <span style="color:#C9A84C;">Stay</span></strong>`. Piège
  rencontré : le sélecteur générique `.xx-brand-text span` (sous-titre "SaaS
  Hôtelier") matchait aussi le nouveau span imbriqué dans `<strong>` et lui
  imposait `font-size:9px`/`uppercase`/`letter-spacing` — corrigé en passant
  ces règles en enfant direct (`.xx-brand-text > span`) dans `login.css`,
  `register.css`, `checkout.css`. Également retiré les tirets cadratins
  (`—`) des phrases visibles de `install.php` et `register.php` (remplacés
  par point, virgule ou parenthèses selon le cas) à la demande du produit.
- **2026-07-23** — Étape 1 de `/register` durcie : nom complet filtré des
  chiffres en direct (`sanitizeName()`), téléphone filtré aux chiffres/`+`/
  espace puis validé contre le plan de numérotation ivoirien (10 chiffres,
  préfixe 01/05/07 avec ou sans `+225` — `isValidCiPhone()`), jauge de
  robustesse du mot de passe (`passwordScore`, 4 segments) et indicateur de
  correspondance live sur "Confirmer MDP". Tout dans `register.js` +
  `register.php` (classes `.rg-pwd-meter*` dans `register.css`, variante
  mobile incluse).
- **2026-07-23** — Champ **Ville** ajouté à l'étape 2 de `/register` (formulaire,
  `registerPage()` dans `register.js`, `AuthController::register`). Constat fait
  en simulant un parcours SaaS complet (Playwright) : l'inscription ne
  demandait jamais la ville, donc `establishments.city` restait `NULL` tant
  que le owner n'allait pas manuellement dans Paramètres → Identité —
  établissement invisible en recherche vitrine sans aucun avertissement.
  Champ requis côté client (même logique que `establishment_name`, validé
  dans `submit()` avant l'appel API), transmis à `Establishment::create()`.
  Paramètres → Identité reste le point de correction a posteriori si besoin.
- **2026-07-23** — Partie B de `todo_list.md` implémentée : sauvegarde
  quotidienne de la base de données, aucun shell/cron en prod donc même
  mécanique que les jobs existants (`Services\SchedulerService`, déclenché
  à la 1ère requête authentifiée du jour).
  - **`Services\BackupService`** (nouveau) : dump SQL pur PHP/PDO (pas de
    dépendance à `mysqldump`), streaming direct vers un flux gzip
    (`gzopen`/`gzwrite`, jamais tout le dump ni même une table entière en
    mémoire — `SHOW TABLES` dynamique, `SHOW CREATE TABLE` par table,
    `INSERT` par lots de 500 lignes via `LIMIT offset,500` ordonné sur la
    PK détectée par `SHOW KEYS` — pas toujours `id`, ex. `rate_limits`.
    `bucket`, `token_blacklist`.`jti`). Écriture dans un `.part` temporaire
    puis `rename()` atomique — jamais de `.gz` à moitié écrit visible.
    Nom : `backup_{Y-m-d}_{His}.sql.gz` dans `storage/backups/` (hors
    `public/`, jamais accessible par URL). Rétention 7 jours glissants
    (`prune()`, purge par tri lexicographique du nom = tri chronologique).
  - **`SchedulerService`** : job `db_backup` ajouté à `JOBS` +
    `runDbBackup()`. **`scripts/check_db_backup.php`** : déclenchement
    manuel/debug, même forme que les 4 scripts `check_*.php` existants.
  - **`/admin/backups`** (nouvelle page superadmin) : liste les dumps,
    bouton "Sauvegarder maintenant" (POST synchrone — un dump prend
    quelques secondes au volume actuel), téléchargement via le pattern
    fetch+blob déjà utilisé pour les PDF (`saas-billing.js`) puisque l'auth
    est un Bearer token en header, pas un cookie. Nouveau contrôleur dédié
    `AdminBackupController` (`index`/`store`/`download`), routes API
    `/api/admin/backups*` gardées `['auth','role:superadmin']`. Le
    téléchargement valide le nom de fichier contre une regex stricte
    (`^backup_\d{4}-\d{2}-\d{2}_\d{6}\.sql\.gz$`) **avant** tout accès
    disque — protection path traversal sur un endpoint qui sert des
    fichiers par nom fourni.
  - **Aucune migration DB** : `scheduled_job_runs` déjà générique accepte
    la nouvelle valeur `job_name='db_backup'` sans changement de schéma.
  - **`storage/backups/*.sql.gz` volontairement JAMAIS copié dans `copy/`**
    (ce sont des données, pas du code — seuls les fichiers PHP/JS de la
    fonctionnalité le sont). `storage/` déjà entièrement gitignored.
  - Testé en CLI (`php scripts/check_db_backup.php`) : dump généré et
    réimportable (CREATE TABLE + INSERT valides, FK checks désactivés
    puis réactivés), rétention vérifiée (9 fichiers → 7 après purge, les 2
    plus anciens supprimés). Pages `/admin/backups` et gating de rôle
    **non testés en navigateur réel** (pas d'accès navigateur depuis cet
    environnement) — à vérifier manuellement.
  - Reste dans `todo_list.md` : Partie A (mode hors ligne SaaS), pas
    attaquée dans cette session. Question ouverte #4 du doc (sauvegarder
    aussi les fichiers uploadés) tranchée pour l'instant : non, DB
    uniquement — à reconsidérer plus tard si besoin.
- **2026-07-23** — Lien "Retraits" retiré de la navigation SaaS (sidebar
  desktop `saas-nav-item` + panneau "Menu" mobile `$moreTabs`, tous deux dans
  `saas/layout.php`), suite au verrou paiement en ligne v1 de la veille
  (`online-payment-v1-lock` en mémoire) : la page Retraits ne sert qu'à
  retirer le solde des paiements en ligne encaissés, donc plus rien à y
  faire tant que ce verrou est fermé. La page/route/controller
  (`saas/payouts.php`, `PayoutController`, `/saas/payouts`) restent
  intactes et accessibles par URL directe — seul le lien de nav a disparu,
  même logique "gelé, pas supprimé" que le reste du verrou paiement en
  ligne. Le mapping de titre mobile (`$titles['payouts']`) et la doc
  (`saas/docs.php`) n'ont pas été touchés — la page garde un titre correct
  si on y arrive directement.
- **2026-07-22** — Paiement en ligne des réservations (GeniusPay) verrouillé
  pour la v1 : fonctionnalité complète (frontend + backend) mais désactivée
  partout tant qu'elle n'est pas explicitement rouverte. Nouveau verrou
  global **`ONLINE_PAYMENTS_ENABLED`** (`config/config.php`, défaut `false`,
  lu depuis `.env`) qui prime sur tout le reste :
  - `PublicController::room()` — `room.online_payment_enabled` (consommé par
    `booking.php`/`booking.js` côté vitrine) passe systématiquement à
    `false` si le verrou est fermé, quel que soit le plan de l'établissement
    ou la colonne `establishments.online_payment_enabled`. Le client ne voit
    donc jamais l'option "Payer en ligne" — juste "Payer sur place", avec un
    message "arrive bientôt".
  - `BookingPaymentController::initiate()` — bloqué en tout premier (avant
    même la lecture du plan/réglage établissement) : même un appel API
    direct (bypass du frontend) ne peut pas déclencher GeniusPay.
  - `EstablishController::update()` — un owner ne peut plus (ré)activer
    `online_payment_enabled` tant que le verrou est fermé (évite un réglage
    "actif" qui ne ferait rien).
  - `saas/settings.php` — le toggle "Paiement en ligne" affiche un badge
    "Bientôt" (au lieu de "PRO") et reste désactivé indépendamment du plan ;
    le flag est passé côté JS via `settingsPage(baseUrl, onlinePaymentsEnabled)`
    (2ᵉ argument injecté depuis `ONLINE_PAYMENTS_ENABLED` — pas de `window.*`
    global dans ce projet, les constantes PHP nécessaires au JS passent en
    argument du factory Alpine, cf. `baseUrl` déjà fait pareil).
  **Pour réactiver en v2** : `ONLINE_PAYMENTS_ENABLED=true` dans `.env`
  suffit à tout redébloquer d'un coup (le plan-gate `online_payment_control`
  et le toggle par établissement reprennent alors leur rôle normal).
- **2026-07-22** — Restyle de tous les modaux SaaS (bookings, planning,
  billing, clients, settings, invoices, payments, expenses, payouts).
  Nouveau fichier partagé `public/assets/css/saas-modals.css` (chargé dans
  `saas/layout.php`, même convention que `saas-responsive.css` : jamais
  fusionné dans `saas.css` qui est un build Tailwind) — apporte
  automatiquement à tous les `.saas-modal-header` un liseré doré et un
  bouton fermer unifié (`.saas-modal-close`), plus des classes génériques
  `.modal-card`/`.modal-grid`/`.modal-strip`/`.modal-chip`/`.modal-list-item`
  pour remplacer les anciens blocs "carte grise" en style inline brut dans
  les vues détail (réservation, historique client, récaps facture/paiement).
  Plusieurs modaux formulaire (invoices/payments/expenses/payouts) n'avaient
  jamais eu de bouton fermer visible (fermeture seulement par clic hors
  modal / Échap) — ajouté pour cohérence. Au passage : `Booking::
  findWithDetails()` ne calculait pas `nights` (contrairement à la requête
  de liste) → "undefined nuit" dans le modal détail réservation, corrigé en
  ajoutant `GREATEST(DATEDIFF(check_out, check_in), 1) as nights` à la
  requête.
- **2026-07-20** — Onglet Compte de `/saas/settings` : les deux
  fonctionnalités placeholder ("disponible dans une prochaine mise à jour")
  sont maintenant implémentées.
  - **Changer le mot de passe** : nouvelle route `POST
    /api/auth/change-password` (`AuthController::changePassword()`,
    `['auth']` — accessible à tous les rôles, c'est un onglet commun),
    vérifie le mot de passe actuel (`User::verifyPassword`), même règle de
    complexité que `resetPassword()` (min. 8 caractères, lettre + chiffre),
    rate-limit 5/h par utilisateur. Modal dédiée dans `settings.php` +
    `saas-settings.js::changePassword()`.
  - **Zone de danger (suppression de compte)** : PAS de suppression
    libre-service (cascade établissements/réservations/paiements trop
    risquée en self-service) — le bouton "Contacter le support" ouvre
    désormais un `mailto:support@afristay.ci` pré-rempli (sujet + corps
    avec l'email du compte) au lieu d'être désactivé.
- **2026-07-20** — Onglet Général de `/saas/settings` restructuré en cartes
  indépendantes (Identité, Localisation, Contact, Paiement des réservations,
  Visibilité, Présentation, Horaires, Photos) au lieu d'un formulaire unique
  avec un seul bouton "Enregistrer". Localisation/Paiement/Visibilité/Photos
  s'enregistraient déjà instantanément à l'action (aucun changement) ;
  Identité/Contact/Présentation/Horaires ont chacune désormais leur propre
  bouton + PUT partiel indépendant (`saveIdentity/saveContact/
  savePresentation/saveHours` dans `saas-settings.js`, réutilisant
  `EstablishController::update()` qui n'écrase que les clés reçues). Le
  bouton global "Créer l'établissement" (`saveGeneral()`, désormais POST
  uniquement) ne subsiste que dans le flux de création (`creatingEstab`),
  regroupé dans une dernière carte visible seulement à ce moment — en
  édition, il n'y a plus de sauvegarde globale.

- **2026-07-20** — Email non vérifié : première action réellement gatée
  (`PayoutController::store()`) en plus du simple bandeau non-bloquant. Un
  owner (pas superadmin, pas receptionist — route déjà restreinte à
  owner|superadmin) dont `email_verified_at` est NULL ne peut plus créer de
  demande de retrait Mobile Money — 403 explicite l'invitant à vérifier
  d'abord. Raison : un retrait déplace de l'argent réel, contrairement au
  reste de l'app où l'email non vérifié ne bloque jamais rien (cf.
  "Vérification d'email" plus haut) ; ça garantit aussi une voie de
  récupération de compte avant tout mouvement financier. Autres candidats
  envisagés mais non retenus pour l'instant : changement de mot de passe
  (fonctionnalité pas encore implémentée dans Settings — bouton désactivé
  "disponible dans une prochaine mise à jour") et invitation de membre
  d'équipe (justification jugée plus faible, pas de risque financier direct).
- **2026-07-20** — Fix layout du bandeau de vérification email
  (`saas/layout.php`, `.saas-verify-banner` dans `saas.css`) : le bandeau
  ajouté comme enfant direct de `.saas-layout` (display:flex, row) devenait
  un item flex à part entière à côté de `<main class="saas-content">` (les
  deux seuls enfants non `position:fixed`/non masqués sur desktop),
  s'affichant comme une colonne pleine hauteur à gauche du contenu au lieu
  d'une bannière horizontale au-dessus. Fix : `flex-wrap:wrap` sur
  `.saas-layout` + le bandeau en `flex:1 1 100%` (force son propre "line"
  de wrap) avec `margin-left:260px`/`margin-top:64px` (même offset que
  `.saas-content` pour compenser sidebar/topbar fixes) et
  `margin-bottom:-64px` (compense le margin-top:64px de `.saas-content` qui
  suit immédiatement, sinon double décalage vertical) ; tout remis à 0 dans
  le breakpoint mobile existant (`.saas-layout{display:block}` y désactive
  déjà le flex row, donc les margins fixes n'ont plus lieu d'être). **Piège
  à retenir** : `.saas-layout` n'a que 2 enfants "réels" en flux desktop
  (sidebar/topbar sont `position:fixed`, mobile-header/establishment sont
  masqués) — tout nouvel élément ajouté directement comme enfant de
  `.saas-layout` (pas dans `<main>`) doit soit être `position:fixed`
  lui-même, soit suivre ce même pattern flex-wrap.
- **2026-07-20** — Audit complet des contrôleurs saas suite au bug de scoping
  clients (voir entrée suivante). Deux familles de faille trouvées et
  corrigées :
  1. **IDOR critique** — `DashboardController::stats()` et `::planning()`
     (`/api/dashboard/stats`, `/api/dashboard/planning`) prenaient
     `establishment_id` directement dans la requête **sans jamais appeler
     `Guard::requireEstablishment()`** — n'importe quel utilisateur
     authentifié (n'importe quel rôle, n'importe quel établissement)
     pouvait lire le CA/dépenses/net profit/historique de réservations de
     n'importe quel AUTRE établissement de la plateforme en changeant ce
     paramètre. Même classe de faille que celle déjà corrigée sur
     `ReportController::summary()` (commentaire "Faille corrigée" déjà en
     place là-bas) — restée non appliquée sur Dashboard. Fix : ajout de
     `Guard::requireEstablishment($estabId)`.
  2. **Contournement de plan-gate multi-établissements** —
     `ExpenseController`, `InvoiceController`, `PayoutController` (méthode
     `gate()`) et `SubscriptionController` (status/history/cancel/downgrade/
     initiate) vérifiaient le plan/l'abonnement via
     `$user['establishment_id']` — figé sur le PREMIER établissement créé à
     l'inscription — au lieu de l'établissement réellement ciblé par la
     requête (`Guard::resolveEstabId()`). Pour un owner multi-établissements
     (plan Business), ceci pouvait à la fois débloquer à tort une
     fonctionnalité payante sur un établissement moins bien loti, ET
     bloquer à tort un établissement qui y avait droit ; `SubscriptionController`
     ne pouvait tout simplement jamais gérer l'abonnement d'un
     établissement autre que le premier. Fix : `gate()` prend désormais
     l'`$estabId` déjà résolu (requête → sinon fallback existant), et les
     notifications qui utilisaient la même valeur figée (invoice
     sent/paid, payment received) sont corrigées au passage.
  **Contrôleurs audités et jugés sains** (Guard::require*/resolveEstabId
  déjà corrects) : RoomController, BookingController, TeamController,
  EstablishController, NotificationController, PushController, ReportController,
  AdminController. `ClientController` — voir entrée dédiée ci-dessous.
- **2026-07-20** — Fix `ClientController::index()` (`/api/clients`) : ne
  filtrait jamais par établissement sélectionné, seulement par périmètre
  owner complet (`Guard::establishmentIds()` = tous les établissements
  possédés). Un owner multi-établissements voyait donc les clients de tous
  ses établissements mélangés sur `/saas/clients`, quel que soit le
  sélecteur actif. Corrigé via `Guard::resolveEstabId($req)`, même pattern
  que `BookingController::index()` — scope à l'établissement actif transmis
  par le front (`?establishment_id=`), erreur si absent.
- **2026-07-20** — Fix "Revenu total clients" toujours à 0 FCFA sur
  `/saas/clients` : `PublicClient::allWithBookingCount()` ne calculait
  jamais `total_spent` (le champ n'existait pas côté backend, le front
  retombait sur `?? 0`). Ajouté via somme des paiements `completed`
  (jointure `bookings → invoices → payments`, cohérent avec `paid_amount`
  dans `Booking::findWithDetails()` et le calcul de revenu de
  `ReportController`) — pas le montant réservé brut, qui inclurait des
  séjours non payés. `COUNT(DISTINCT b.id)` requis pour `booking_count`
  (la jointure paiements démultiplie les lignes en cas de paiement
  partiel). Même bug touchait le modal détail client (`ClientController::show`
  ne renvoyait que la ligne brute `public_clients`, jamais d'historique ni
  d'agrégats) — corrigé via `PublicClient::withStats()` +
  `Booking::historyForClient()`.
- **2026-07-20** — Nouveau job `SchedulerService::runExpirePendingBookings()`
  (4ᵉ job de `JOBS`, script CLI debug `scripts/check_expired_bookings.php`) :
  une réservation en ligne `pending` (`PublicController`, jamais confirmée
  par l'établissement) dont `check_in` est dépassé est désormais
  auto-annulée (`status = 'cancelled'`), avec la même notification équipe +
  email voyageur que `BookingController::destroy()`. Avant ce job, une
  réservation `pending` restait bloquante indéfiniment dans
  `Booking::isRoomAvailable()` (qui n'exclut que `cancelled`/`checked_out`).
- **2026-07-20** — Même famille de bug sur le tableau de bord (`dashboard.php`) :
  l'action rapide "Rapports" et la carte "Résumé financier" n'avaient aucune
  garde `canSeeFinance`. Pire : `DashboardController::stats()`
  (`/api/dashboard/stats`, route `['auth']` sans restriction de rôle)
  renvoyait déjà revenue/expenses/payments_*/net_profit à n'importe quel
  authentifié de l'établissement — masquer côté UI n'aurait pas suffi
  (visible dans l'onglet Network). Filtré côté serveur (`$canSeeFinance`
  dans le contrôleur) + UI (carte masquée, action verrouillée avec cadenas
  comme les autres nav items `canSeeFinance`/`canSeeSettings`). **Réflexe à
  garder** : quand un bug d'accès client est signalé, vérifier aussi si
  l'API sous-jacente fuite la donnée, pas seulement si le lien/bouton est
  visible.
- **2026-07-20** — Fix accès : un receptionist pouvait atteindre les onglets
  Général/Membres/Abonnement de `/saas/settings` via "Mon profil" ou
  `?tab=`, aucun de ces chemins ne vérifiait le rôle (seul le lien sidebar
  le faisait). `saas-settings.js` force maintenant `activeTab='account'`
  pour tout rôle ≠ owner/superadmin, indépendamment du point d'entrée.
  Pattern à surveiller : toute page avec plusieurs points d'entrée doit
  vérifier les droits à l'arrivée sur la page elle-même, pas seulement sur
  le lien qui y mène.
- **2026-07-20** — Bouton "Déconnexion" ajouté au panneau mobile "Menu"
  (`saas-mobile-more-panel`) — absent sur petit écran, seul le menu
  déroulant desktop l'avait.
- **2026-07-20** — Vérification d'email à l'inscription (non bloquante,
  bandeau + renvoi). Nouvelle table `email_verifications`. Correctif
  `Response.php` (liste blanche pages autonomes). Mise à niveau complète de
  `copy/scripts/schema.sql` et `wipe-db.sql` (très en retard).
- **2026-07-19** — Bouton retour mobile ajouté sur login/register/
  forgot-password/reset-password (`.lg-mobile-back`/`.rg-mobile-back`).
  Images vitrine auto-hébergées (`public/assets/img/vitrine/`), suppression
  dépendance images.unsplash.com (CSP). Navbar vitrine toujours en mode
  "pilule" (retrait de l'état transparent initial sur `/`).
- **2026-07-19** — Bug slug établissement : `slug` non généré pour les
  comptes créés via `AuthController::register()` (seul
  `EstablishController::store()` le faisait) — corrigé, + backfill des
  établissements existants en prod (slug NULL → généré via `Core\Slug`).
- **2026-07-18** — `wipe-db.sql` complété (9 tables manquantes :
  guest_push_subscriptions, payout_requests, password_resets, webauthn_*,
  device_attestations, token_blacklist, rate_limits, scheduled_job_runs).
- **2026-07-17** — Espace admin, paiements bailleurs (payout_requests),
  push notifications, durcissement sécurité (rate_limits, token_blacklist),
  gate WebAuthn retiré de la connexion (friction jugée excessive), gel des
  établissements en excédent après downgrade.
