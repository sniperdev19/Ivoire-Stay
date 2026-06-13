# Architecture Frontend — Ivoire Stay

## Vue d'ensemble

Le frontend est découpé en **deux espaces distincts**, tous deux servis par le même backend PHP :

| Espace | URL | Cible | Rôle |
|---|---|---|---|
| **Vitrine** (B2C) | `/`, `/search`, `/property/{id}`… | Voyageurs | Site public, recherche & réservation |
| **SaaS** (B2B) | `/login`, `/saas/*` | Hôteliers | Dashboard de gestion hôtelière |

L'authentification est **100 % côté client** : JWT stocké en `localStorage`, transmis via `Authorization: Bearer <token>`. Aucun cookie, aucune session PHP.

---

## Structure des fichiers

```
public/
├── index.php                  ← point d'entrée unique (front controller)
├── .htaccess                  ← rewrite tout vers index.php
└── assets/
    └── uploads/               ← photos uploadées (gitignorées)

src/
├── templates/
│   ├── 404.php
│   ├── saas/
│   │   ├── layout.php         ← wrapper HTML authentifié (sidebar + topnav)
│   │   ├── login.php
│   │   ├── register.php
│   │   ├── install.php
│   │   ├── dashboard.php
│   │   ├── planning.php
│   │   ├── rooms.php
│   │   ├── bookings.php
│   │   ├── clients.php
│   │   ├── invoices.php
│   │   ├── payments.php
│   │   ├── expenses.php
│   │   ├── reports.php
│   │   ├── settings.php
│   │   └── help.php
│   └── vitrine/
│       ├── layout.php         ← wrapper HTML public (nav + footer)
│       ├── home.php
│       ├── apropos.php
│       ├── contact.php
│       ├── pricing.php
│       ├── search.php
│       ├── property.php
│       └── booking.php
└── Controllers/
    └── PageController.php     ← sert les pages HTML
```

---

## Mécanisme de rendu (`Response::render`)

`Response::render(string $template, array $data)` dans `src/Core/Response.php` :

1. Extrait `$data` → variables PHP disponibles dans le template
2. Capture le rendu du template dans un output buffer (`ob_start`)
3. Injecte `$content` dans le layout correspondant

```
Pages SaaS (hors login/register/install)  →  saas/layout.php  +  $content
Pages Vitrine                              →  vitrine/layout.php  +  $content
Pages autonomes (login, register, install) →  output direct (pas de layout)
```

**Exemple depuis un contrôleur :**
```php
Response::render('saas/dashboard', [
    'title' => 'Tableau de bord',
    'page'  => 'dashboard',
]);
```

---

## Routes HTML — Vitrine (public, sans auth)

| URL | Template | Variables PHP disponibles |
|---|---|---|
| `/` | `vitrine/home` | `$title` |
| `/apropos` | `vitrine/apropos` | `$title` |
| `/contact` | `vitrine/contact` | `$title` |
| `/tarifs` | `vitrine/pricing` | `$title` |
| `/search` | `vitrine/search` | `$title` |
| `/property/{id}` | `vitrine/property` | `$title`, `$property_id` |
| `/booking/{id}` | `vitrine/booking` | `$title`, `$room_id` |

---

## Routes HTML — SaaS (auth gérée côté JS)

| URL | Template | Variables PHP disponibles |
|---|---|---|
| `/login` | `saas/login` | `$title` |
| `/register` | `saas/register` | `$title` |
| `/install` | `saas/install` | `$title` |
| `/saas` | `saas/dashboard` | `$title`, `$page = 'dashboard'` |
| `/saas/planning` | `saas/planning` | `$title`, `$page` |
| `/saas/rooms` | `saas/rooms` | `$title`, `$page` |
| `/saas/bookings` | `saas/bookings` | `$title`, `$page` |
| `/saas/clients` | `saas/clients` | `$title`, `$page` |
| `/saas/invoices` | `saas/invoices` | `$title`, `$page` |
| `/saas/payments` | `saas/payments` | `$title`, `$page` |
| `/saas/expenses` | `saas/expenses` | `$title`, `$page` |
| `/saas/reports` | `saas/reports` | `$title`, `$page` |
| `/saas/settings` | `saas/settings` | `$title`, `$page` |
| `/saas/help` | `saas/help` | `$title`, `$page` |

---

## Flux d'authentification SaaS (côté JS)

```
1. GET /login
   └── PHP retourne la page HTML (shell vide)

2. Formulaire soumis côté JS
   POST /api/auth/login  { email, password }
   └── Réponse : { success, data: { token, user, establishments } }

3. JS stocke dans localStorage :
   ├── token              (JWT Bearer)
   ├── user               (id, role, name, email)
   └── establishment_id   (établissement actif)

4. Toutes les requêtes API suivantes :
   Authorization: Bearer <token>

5. Sur erreur 401 → JS vide localStorage et redirige vers /login
```

---

## Appels API par page SaaS

### `/saas` — Tableau de bord
```
GET /api/dashboard/stats?establishment_id=X
GET /api/dashboard/planning?establishment_id=X
```
Données reçues : `revenue`, `occupancy_rate`, `active_bookings`, `new_bookings`, `available_rooms`, `total_rooms`, `expenses`, `payments_received`, `payments_pending`, `net_profit`, `distribution`, `recent_bookings`, `month`

### `/saas/rooms` — Chambres & Tarifs
```
GET  /api/rooms?establishment_id=X
GET  /api/room-types?establishment_id=X
POST /api/rooms                     (créer)
PUT  /api/rooms/{id}                (modifier)
POST /api/rooms/{id}/status         (changer statut)
POST /api/rooms/{id}/photos         (upload photo)
DELETE /api/room-photos/{id}        (supprimer photo)
DELETE /api/rooms/{id}              (supprimer chambre)
POST /api/room-types                (créer type)
PUT  /api/room-types/{id}           (modifier type)
DELETE /api/room-types/{id}         (supprimer type)
```

### `/saas/bookings` — Réservations
```
GET    /api/bookings?establishment_id=X
POST   /api/bookings
GET    /api/bookings/{id}
PUT    /api/bookings/{id}
DELETE /api/bookings/{id}
POST   /api/bookings/{id}/checkin
POST   /api/bookings/{id}/checkout
```

### `/saas/planning` — Planning
```
GET /api/planning?establishment_id=X
```

### `/saas/clients` — Clients
```
GET    /api/clients?establishment_id=X
GET    /api/clients/{id}
PUT    /api/clients/{id}
DELETE /api/clients/{id}
```

### `/saas/invoices` — Facturation
```
GET  /api/invoices?establishment_id=X
POST /api/invoices
GET  /api/invoices/{id}
PUT  /api/invoices/{id}
GET  /api/invoices/{id}/pdf
```

### `/saas/payments` — Paiements
```
GET  /api/payments?establishment_id=X
POST /api/payments
PUT  /api/payments/{id}
```

### `/saas/expenses` — Dépenses
```
GET    /api/expenses?establishment_id=X
POST   /api/expenses
PUT    /api/expenses/{id}
DELETE /api/expenses/{id}
```

### `/saas/settings` — Paramètres
```
GET /api/establishments/{id}
PUT /api/establishments/{id}
POST /api/establishments/{id}/photo
GET  /api/subscriptions/status
GET  /api/subscriptions/plans
POST /api/subscriptions/initiate
```

---

## Appels API — Vitrine publique (sans auth)

### `/search`
```
GET /api/public/search?city=...&type=...&check_in=...&check_out=...
GET /api/public/destinations
```

### `/property/{id}`
```
GET /api/public/property/{id}
GET /api/public/availability/{id}
```

### `/booking/{id}`
```
GET  /api/public/rooms/{id}
POST /api/public/booking  { room_id, check_in, check_out, client_name, client_email, client_phone }
```

---

## Gestion multi-tenant côté JS

Un `owner` peut posséder plusieurs établissements. Le JS doit :

1. Au login → stocker la liste `establishments[]` retournée par l'API
2. Afficher un sélecteur d'établissement dans le layout SaaS
3. Mettre à jour `localStorage.establishment_id` au changement
4. Relancer les appels API avec le nouvel `establishment_id`
5. Restreindre l'UI selon le rôle (`receptionist` n'a pas accès aux paramètres ni aux rapports financiers)

Un `receptionist` a son `establishment_id` encodé directement dans le token JWT → pas de sélecteur.

---

## Rôles et restrictions UI

| Rôle | Pages accessibles | Sections à masquer |
|---|---|---|
| `superadmin` | Tout | — |
| `owner` | Tout | — |
| `receptionist` | Chambres, réservations, clients, planning | Factures, paiements, dépenses, rapports, paramètres |

> La restriction est appliquée **côté API** (middleware PHP). Le frontend doit masquer les sections inaccessibles pour l'UX, mais le backend rejette de toute façon les requêtes non autorisées.

---

## Stack frontend recommandée

| Besoin | Choix |
|---|---|
| CSS | Tailwind CSS (CDN Play pour démarrer, Vite pour la prod) |
| JS | Vanilla JS ou Alpine.js (léger, aucun build requis) |
| Build (optionnel) | Vite + Tailwind → `/public/build/` (déjà gitignorée) |
| Icônes | Heroicons (SVG inline) ou Lucide |
| Requêtes API | `fetch()` natif avec wrapper autour du token Bearer |

### Wrapper fetch minimal recommandé

```js
const api = async (method, path, body = null) => {
    const token = localStorage.getItem('token');
    const estId = localStorage.getItem('establishment_id');
    const url   = path.includes('?')
        ? `${path}&establishment_id=${estId}`
        : `${path}?establishment_id=${estId}`;

    const res = await fetch(url, {
        method,
        headers: {
            'Content-Type': 'application/json',
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
        },
        body: body ? JSON.stringify(body) : null,
    });

    if (res.status === 401) {
        localStorage.clear();
        location.href = '/login';
    }

    return res.json();
};
```
