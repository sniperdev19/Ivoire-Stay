# Architecture Projet — Ivoire Stay

## Présentation

**Ivoire Stay** est une plateforme double-usage :

| Produit | Cible | URL | Description |
|---|---|---|---|
| **SaaS B2B** | Hôteliers / résidences | `/login`, `/saas/*` | Dashboard de gestion hôtelière (chambres, réservations, facturation, dépenses) |
| **Vitrine B2C** | Voyageurs | `/`, `/search`, `/property/{id}`… | Portail public de recherche et réservation en ligne |

Les deux partagent le même backend PHP, la même base de données MySQL et la même API REST.

---

## Stack technique

| Couche | Technologie |
|---|---|
| Langage backend | PHP 8.0+ |
| Framework | Maison (MVC léger, pas de framework tiers) |
| Base de données | MySQL 8.0+ — charset `utf8mb4` |
| Authentification | JWT HS256 (Bearer token, localStorage côté client) |
| Envoi d'emails | PHPMailer 6.9 (SMTP) |
| Génération PDF | FPDF 1.8 (factures) |
| Paiement | GeniusPay (API ivoirienne, HMAC-SHA256 webhook) |
| Serveur web | Apache 2.4 + mod_rewrite + mod_fcgid |
| Gestionnaire de dépendances | Composer (autoload PSR-4) |
| Frontend (prévu) | Vanilla JS ou Alpine.js + Tailwind CSS |

---

## Structure des dossiers

```
Ivoire Stay/
├── public/                  ← seul dossier exposé au web
│   ├── index.php            ← point d'entrée unique (front controller)
│   ├── .htaccess            ← rewrite toutes les URLs vers index.php
│   └── assets/uploads/      ← photos et reçus uploadés
│
├── config/
│   ├── config.php           ← charge .env, définit les constantes PHP
│   ├── routes.php           ← ~81 routes GET/POST/PUT/DELETE
│   └── plans.php            ← définition des 3 plans d'abonnement
│
├── src/
│   ├── Core/                ← framework maison (Router, Request, Response…)
│   ├── Controllers/         ← logique HTTP (11 contrôleurs)
│   ├── Models/              ← accès base de données (PDO, mini-ORM)
│   ├── Services/            ← logique métier transverse (JWT, PDF, paiement…)
│   └── templates/           ← templates PHP (SaaS + Vitrine)
│       ├── saas/
│       └── vitrine/
│
├── vendor/                  ← dépendances Composer (gitignorées)
├── .env                     ← secrets (gitignorés)
├── .env.example             ← modèle de configuration
├── .gitignore
├── composer.json
├── architecture_projet.md   ← ce fichier
├── archi_backend.md
└── archi_frontend.md
```

---

## Cycle de vie d'une requête

```
Navigateur / Client API
       │
       ▼
Apache .htaccess  →  public/index.php
       │
       ▼
config/config.php       (constantes, .env)
config/routes.php       (enregistrement des routes)
       │
       ▼
Core\Router::dispatch()
       │
       ├── Middleware::handle()   → vérifie JWT + rôle
       ├── Guard::check()         → isole les données par establishment_id
       │
       ▼
Controllers\XxxController::action()
       │
       ├── Models\Xxx::query()    → SQL via PDO
       └── Services\Xxx::logic()  → métier (JWT, PDF, paiement…)
       │
       ▼
Core\Response::json()   (API)
Core\Response::render() (HTML)
```

---

## Modèle multi-tenant

Chaque établissement est une entité isolée. Toutes les données (chambres, réservations, clients, factures, dépenses) sont filtrées par `establishment_id`.

- Un **owner** peut posséder plusieurs établissements (plan `business`) ou un seul (plans `starter` / `pro`)
- Un **receptionist** est rattaché à un seul établissement (stocké dans son token JWT)
- Le **Guard** PHP vérifie à chaque requête que l'utilisateur a accès à l'`establishment_id` demandé

---

## Rôles utilisateurs

| Rôle | Description | Périmètre |
|---|---|---|
| `superadmin` | Administrateur plateforme | Tous les établissements, gestion abonnements |
| `owner` | Propriétaire hôtelier | Ses établissements, toutes les fonctions métier |
| `receptionist` | Réceptionniste | Un seul établissement, sans accès financier ni paramètres |

---

## Plans d'abonnement

| Plan | Prix/mois | Chambres | Établissements | Features |
|---|---|---|---|---|
| `starter` | Gratuit | 10 max | 1 | Chambres, réservations basiques |
| `pro` | 9 000 FCFA | Illimité | 1 | + Factures, paiements, PDF, dépenses, rapports |
| `business` | 20 000 FCFA | Illimité | Illimité | + Multi-établissements, boost vitrine |

Le `PlanGate` PHP bloque les accès aux features non incluses dans le plan actif (HTTP 403 + `upgrade_required`).

---

## Environnement (`.env`)

```
APP_ENV=development|production
APP_URL=http://localhost/PROJETS/Ivoire%20Stay/public

DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS

JWT_SECRET      (≥ 32 chars — obligatoire en production)
JWT_EXPIRY      (secondes, défaut 86400 = 24h)

MAIL_HOST, MAIL_PORT, MAIL_USER, MAIL_PASS, MAIL_FROM, MAIL_FROM_NAME

GENIUS_PAY_KEY, GENIUS_PAY_URL, GENIUS_PAY_WEBHOOK_SECRET

UPLOAD_MAX_SIZE (octets, défaut 5 242 880 = 5 Mo)
```

---

## Dépendances Composer

```json
"phpmailer/phpmailer": "^6.9"
"setasign/fpdf":       "^1.8"
```

Autoload PSR-4 :
```
Core\        → src/Core/
Controllers\ → src/Controllers/
Models\      → src/Models/
Services\    → src/Services/
```

---

## Documentation complémentaire

- [archi_backend.md](archi_backend.md) — Couche Core, contrôleurs, modèles, services, base de données, routes API
- [archi_frontend.md](archi_frontend.md) — Templates, rendu PHP, flux auth JS, appels API par page
