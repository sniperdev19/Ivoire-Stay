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
déployé en production** (le serveur de prod n'a qu'un accès phpMyAdmin, pas
de shell/git — déploiement par upload manuel de fichiers).

**Règle impérative : toute modification dans `src/`, `public/`, `config/` ou
`scripts/` doit être répliquée à l'identique dans `copy/`**, sauf si
explicitement dit le contraire. Avant de considérer une tâche terminée,
vérifier que les deux arborescences sont resynchronisées (`diff` rapide si
doute). `copy/scripts/schema.sql` et `copy/scripts/wipe-db.sql` ont déjà
dérivé une fois par le passé (remis à niveau le 2026-07-20) — rester
vigilant sur ces fichiers de référence en particulier, faciles à oublier
car non exécutés automatiquement.

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
comme canal (décision produit).

## Journal des évolutions récentes

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
