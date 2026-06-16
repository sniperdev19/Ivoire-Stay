# Modifications — Session 1

Récapitulatif de toutes les modifications effectuées sur le projet Ivoire Stay.

---

## 1. Correction du bug `$base_url` (erreur 500 + Alpine cassé)

- **Cause** : `Response::render()` n'injectait jamais `$base_url`, alors que tous les templates l'utilisent → `Warning: Undefined variable $base_url` injecté dans le HTML (via Xdebug en mode dev), ce qui corrompait les attributs Alpine `x-data` de la page d'accueil.
- **Fix** : ajout de `$base_url = APP_URL;` avant `extract($data)` dans `src/Core/Response.php`.
- Création du dossier `public/assets/` et copie de `logo.png` dedans.

## 2. Correction des appels API en chemin racine (404)

- **Cause** : `fetch('/api/public/...')` (chemin absolu depuis la racine) ne tenait pas compte du sous-dossier `/PROJETS/Ivoire Stay/public` → 404 → `property`/`room` restaient `null` → erreurs Alpine en cascade.
- **Fix** : préfixe `apiBase`/`baseUrl` sur tous les `fetch` de `vitrine/property.php` et `vitrine/booking.php`.

## 3. Refactor CSS/JS — sortie des templates vers `public/assets/`

- **Structure créée** :
  - `public/assets/css/` : `vitrine.css`, `saas.css` (globaux par espace) + `css/pages/*.css` (par page).
  - `public/assets/js/` : `vitrine.js`, `saas.js` (globaux) + `js/pages/*.js` (par page).
- **Mécanisme** : chaque template déclare `$pageCss` / `$pageJs` en tête ; les 2 layouts chargent automatiquement les `<link>` / `<script>` correspondants. **Alpine est chargé en dernier** pour que les composants `x-data` (fonctions externalisées) soient définis avant son init.
- Tous les blocs `<style>` extraits en CSS, et chaque gros `x-data="{…}"` transformé en fonction (`homeCarousel()`, `dashboardPage(baseUrl)`, `loginForm(baseUrl)`, etc.). Les `x-data` triviaux restent inline.
- **Carrousel accueil** : données construites en PHP et injectées en JSON (`json_encode` + `htmlspecialchars`) → élimine le bug d'échappement des apostrophes.
- **Bug corrigé** dans `clients` : `a ?? b || c` (erreur de syntaxe JS, mélange `??`/`||`) → parenthèses ajoutées.

## 4. Avertissements Intelephense (variables injectées par `render()`)

- Ajout d'annotations `@var` + garde de repli (`?? rtrim(APP_URL, '/')` / `?? null`) pour `$base_url`, `$content`, `$title`, `$property_id`, `$room_id` dans les templates concernés (layouts, login, register, property, booking, et les 16 pages utilisant `$base_url`).
- Suppression d'un BOM parasite sur `rooms.php`.

## 5. Images

- Téléchargement de 7 images libres (Unsplash) placées dans `public/assets/` :
  `bg_auth.jpg`, `bg_home.jpg`, `back_destination.jpg`, `carrouss1.jpg` → `carrouss4.jpg` (+ `logo.png` déjà présent).
- Corrige les 500 sur fonds manquants (login, accueil, carrousel).

## 6. Tailwind compilé (fin du CDN)

- Téléchargement de la CLI autonome `tailwindcss.exe` (v3.4.17, gitignorée).
- `tailwind.config.js` : scan de `src/templates/**/*.php` **et** `public/assets/js/**/*.js`.
- **Deux builds par espace** : `src/vitrine.input.css` → `public/assets/css/vitrine.css` et `src/saas.input.css` → `public/assets/css/saas.css`.
  Chaque feuille = Tailwind (base + utilitaires) **+** styles composant de l'espace en `@layer components` → **les utilitaires Tailwind l'emportent** sur les classes composant (corrige le bug « fond blanc qui gagne »).
- Remplacement du `<script cdn.tailwindcss.com>` par les `<link>` compilés dans les 2 layouts.
- Script `build-css.sh` (`./build-css.sh` ou `./build-css.sh watch`) + `AddType application/manifest+json` (voir §9) — README mis à jour.

## 7. Page 404

- Création de `src/templates/404.php` (page autonome, branchée via `Router::dispatch`).
  Les routes `/api/...` inexistantes renvoient du JSON, les autres la page 404 HTML (design vitrine : cream/or/forêt, boutons Accueil / Explorer / Contact).

## 8. Corrections d'affichage

- **Navbar vitrine responsive** : suppression du `style="display:flex"` inline qui écrasait `hidden` → bascule en classes Tailwind `hidden md:flex`. Liens desktop + Connexion/Commencer masqués sur mobile (burger seul).
- **Carrousel accueil responsive** : `styleFor()` adaptatif (3 paliers desktop/tablette/mobile) + listener resize ; conteneur `.dest-carousel-track` avec media queries.
- **Page Tarifs** :
  - Prix Pro manquant → `x-data="{ annual:false }"` englobe désormais hero + cartes (le prix `9 000 FCFA` s'affiche).
  - Carte « Business » : texte invisible (blanc sur blanc) → retrait de `glass-card`, style sombre explicite.
  - Texte tronqué « Priorit » → « Prioritaire ».

## 9. Système PWA (connexion réservée à l'app installée)

- **Manifest** `public/manifest.webmanifest` (`display: standalone`, `start_url: login`, icônes 192/512/maskable).
- **Service worker** `public/sw.js` (network-first + repli cache, GET same-origin only).
- **Helper** `public/assets/js/pwa.js` : enregistrement SW, détection standalone/install, capture `beforeinstallprompt`, composant Alpine `pwaGate()`.
- Icônes dans `public/assets/icons/` ; `AddType application/manifest+json .webmanifest` dans `public/.htaccess`.
- Branchement manifest + `pwa.js` dans les 2 layouts, `login.php`, `register.php`.
- **Gating connexion** (`login.php`) : le formulaire n'existe (`<template x-if="standalone">`) que dans l'app installée ; sinon écran d'installation (bouton natif Android/PC, instructions iOS).
- **Install obligatoire après inscription** : `register.js` redirige vers `/login?registered=1` (au lieu de `/saas`).
- **Garde standalone** sur le shell SaaS (`saas.js`) : tout accès hors app redirige vers `/login`.
- Mémoire projet : `pwa-app-only-login.md`.

## 10. Affichage de l'écran d'installation

- Ajout d'une carte glassmorphism sombre `.install-card` derrière le contenu du gate (texte lisible sur la photo de fond).

## 11. Nettoyage des accès connexion / inscription

- **`login.php`** : suppression de la navbar du haut ; les liens « Créer un compte » restants ouvrent dans un **navigateur externe** (`target="_blank" rel="noopener"`) — l'inscription doit se faire hors app.
- **Vitrine** (`vitrine/layout.php`) : suppression du lien **« Connexion »** (navbar desktop, menu mobile, footer). Il ne reste que « Commencer » / « S'inscrire ».

---

## Parcours final

1. Vitrine (navigateur) → **S'inscrire / Commencer**.
2. Inscription → message **« Installez l'application »**.
3. Installation de la PWA (Android / iOS / PC).
4. Ouverture de l'app → **formulaire de connexion** (uniquement en mode app installée).
5. Connexion → espace SaaS (accessible uniquement depuis l'app).

## Notes / à savoir

- **Rebuild CSS** après ajout de classes Tailwind : `./build-css.sh`.
- **Production = HTTPS obligatoire** pour la PWA (service worker / installation). Sur `localhost`, l'HTTP suffit pour tester.
- Les icônes PWA sont des copies du logo 1254×1254 (fonctionnel) ; remplaçables par de vraies tailles 192/512 (mêmes noms dans `public/assets/icons/`).
- `tailwindcss.exe` et `_backup_templates/` sont gitignorés.
