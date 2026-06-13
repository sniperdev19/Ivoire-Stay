# Architecture Backend — Ivoire Stay

## Couche Core (`src/Core/`)

| Classe | Rôle |
|---|---|
| `Router.php` | Matching d'URL par regex, chaîne de middlewares, dispatch vers le contrôleur |
| `Request.php` | Encapsule la requête HTTP : body JSON/form, query string, headers, Bearer token, fichiers |
| `Response.php` | Helpers de réponse : `json()`, `success()`, `error()`, `render()`, `redirect()`, `notFound()`, `unauthorized()`, `forbidden()` |
| `Database.php` | Singleton PDO : connexion MySQL, requêtes préparées |
| `Middleware.php` | Décode le JWT, vérifie le rôle (`auth`, `role:owner\|superadmin`…) |
| `Guard.php` | Autorisation multi-tenant : vérifie qu'un utilisateur appartient à l'établissement accédé |
| `PlanGate.php` | Feature gating selon l'abonnement actif (starter / pro / business) |

### Response — méthodes statiques

| Méthode | HTTP | Usage |
|---|---|---|
| `success($data, $msg, $status)` | 200 | Réponse API standard |
| `error($msg, $status, $errors)` | 4xx/5xx | Erreur API |
| `render($template, $data)` | 200 | Rendu d'un template PHP |
| `redirect($url, $status)` | 302 | Redirection HTTP |
| `notFound($msg)` | 404 | Route/ressource introuvable |
| `unauthorized($msg)` | 401 | Token manquant ou expiré |
| `forbidden($msg)` | 403 | Rôle ou plan insuffisant |

Format JSON uniforme de toutes les réponses API :
```json
{ "success": true|false, "message": "...", "data": {...}|null, "errors": null }
```

### PlanGate — méthodes publiques

| Méthode | Rôle |
|---|---|
| `getPlan(array $estab): string` | Retourne le plan actif ; si `plan_expires_at` dépassé → fallback `starter` |
| `can(array $estab, string $feature): bool` | Vérifie si une feature est disponible |
| `require(array $estab, string $feature): void` | Bloque (403 + `upgrade_required`) si feature manquante |
| `canAddRoom(array $estab, int $current): bool` | Vérifie si la limite de chambres est atteinte |
| `maxRooms(array $estab): int` | Retourne la limite de chambres (-1 = illimité) |

Features contrôlées : `invoices`, `payments`, `expenses`, `reports`, `pdf`, `boost`, `multi_estab`

---

## Contrôleurs (`src/Controllers/`)

| Contrôleur | Périmètre |
|---|---|
| `AuthController` | Login, register, logout, `/api/auth/me` — génère et valide les JWT |
| `PageController` | Rendu des templates HTML (shell SaaS + Vitrine) |
| `EstablishController` | CRUD établissements, upload photo de couverture |
| `RoomController` | Types de chambres, chambres, photos, statuts, équipements |
| `BookingController` | Réservations : CRUD, check-in / check-out, planning |
| `ClientController` | Gestion des clients internes |
| `InvoiceController` | Facturation : CRUD, génération PDF, paiements associés |
| `ExpenseController` | Charges opérationnelles, upload reçu |
| `SubscriptionController` | Plans d'abonnement, intégration GeniusPay |
| `DashboardController` | KPIs : taux d'occupation, CA, vue calendrier |
| `PublicController` | API publique sans auth : recherche, disponibilités, réservation en ligne |

---

## Modèles (`src/Models/`)

Tous héritent de `BaseModel` qui fournit un mini-ORM PDO :

```php
find(int $id): ?array
all(string $orderBy, string $dir): array
where(array $conditions, string $orderBy, string $dir): array
first(array $conditions): ?array
create(array $data): int          // retourne lastInsertId
update(int $id, array $data): bool
delete(int $id): bool
count(array $conditions): int
```

| Modèle | Table | Méthodes spécifiques notables |
|---|---|---|
| `User` | `users` | `findByEmail()`, `verifyPassword()`, `hashPassword()` (bcrypt), `safe()` (masque le hash) |
| `Establishment` | `establishments` | `findByOwner()`, `withStats()`, `forUser()` |
| `Room` | `rooms` | Statuts : `available / occupied / cleaning / maintenance / blocked` |
| `RoomType` | `room_types` | Tarifs : `base_price`, `weekend_price`, `passage_price` |
| `Booking` | `bookings` | `allWithDetails()`, `isRoomAvailable()`, `calculateAmount()`, `forPlanning()` |
| `Invoice` | `invoices` | 1-to-1 avec booking, statuts : `draft → sent → paid` |
| `Payment` | `payments` | Types : `deposit / full / partial` ; méthodes : `cash / mobile_money / card / bank_transfer` |
| `Expense` | `expenses` | Par établissement, avec reçu uploadé |
| `PublicClient` | `public_clients` | Clients du portail B2C (sans compte SaaS) |

---

## Services (`src/Services/`)

| Service | Rôle |
|---|---|
| `AuthService` | JWT HS256 : `encode()` (login) / `decode()` (vérification), expiry configurable via `JWT_EXPIRY` |
| `PdfService` | Génération de factures PDF via FPDF ; fallback HTML si FPDF absent |
| `GeniusPayService` | Intégration paiement GeniusPay : initiation, vérification, webhook HMAC-SHA256 |
| `CalendarService` | Calendrier de disponibilité : plages de dates, détection de conflits |
| `UploadService` | Validation MIME, stockage fichiers (photos chambres, reçus), nettoyage |

### GeniusPayService — méthodes publiques

| Méthode | Rôle |
|---|---|
| `initiate(array $params): array` | Initie un paiement → retourne `payment_url`, `token`, `reference` |
| `verify(string $reference): array` | Vérifie le statut d'un paiement via l'API GeniusPay |
| `validateWebhook(string $rawBody, string $sig): bool` | Valide la signature HMAC-SHA256 du webhook |
| `mapStatus(string $gpStatus): string` | Convertit statut GP → `active / failed / pending` |

---

## Base de données

MySQL 8.0+, charset `utf8mb4`. 12 tables principales.

### Schéma relationnel

```
establishments ──< rooms ──< bookings >── invoices
     │               │           │
     │           room_types   payments
     │           room_photos
     │           room_amenities
     │
    users ─────────────────────────────── public_clients
                                               │
                                           bookings
expenses  (par établissement)
subscriptions  (par établissement — historique GeniusPay)
```

### Tables clés

| Table | Colonnes importantes |
|---|---|
| `establishments` | `plan` (starter/pro/business), `plan_expires_at`, `type` (hotel/residence), `owner_id`, `is_active` |
| `users` | `role` (superadmin/owner/receptionist/client), `establishment_id`, `password` (bcrypt) |
| `rooms` | `status` (enum), `room_type_id`, `floor`, `number` |
| `room_types` | `base_price`, `weekend_price`, `passage_price`, `establishment_id` |
| `bookings` | `booking_type` (nuit/weekend/passage), `source` (manual/online/phone), `status`, `check_in`, `check_out` |
| `invoices` | `invoice_number` UNIQUE, `amount_ht`, `tax_rate`, `amount_ttc`, `status` (draft/sent/paid) |
| `payments` | `method` (cash/mobile_money/bank_transfer/card), `type` (deposit/full/partial) |
| `subscriptions` | `plan`, `billing` (monthly/yearly), `amount`, `gp_reference`, `gp_token`, `status` (pending/active/failed), `started_at`, `expires_at` |

---

## Routes API (`config/routes.php`)

### Auth

| Méthode | Route | Middleware | Action |
|---|---|---|---|
| POST | `/api/auth/register` | — | Créer un compte owner |
| POST | `/api/auth/login` | — | Connexion → JWT |
| POST | `/api/auth/logout` | `auth` | Déconnexion (stateless) |
| GET | `/api/auth/me` | `auth` | Profil utilisateur courant |

### Établissements

| Méthode | Route | Middleware |
|---|---|---|
| GET | `/api/establishments` | `auth` |
| POST | `/api/establishments` | `auth`, `role:owner\|superadmin` |
| GET | `/api/establishments/{id}` | `auth` |
| PUT | `/api/establishments/{id}` | `auth`, `role:owner\|superadmin` |
| POST | `/api/establishments/{id}/photo` | `auth`, `role:owner\|superadmin` |
| DELETE | `/api/establishments/{id}` | `auth`, `role:superadmin` |

### Types de chambres

| Méthode | Route | Middleware |
|---|---|---|
| GET | `/api/room-types` | `auth` |
| POST | `/api/room-types` | `auth`, `role:owner\|superadmin` |
| PUT | `/api/room-types/{id}` | `auth`, `role:owner\|superadmin` |
| DELETE | `/api/room-types/{id}` | `auth`, `role:owner\|superadmin` |

### Chambres

| Méthode | Route | Middleware |
|---|---|---|
| GET / POST | `/api/rooms` | `auth` / `auth`, `role:owner\|superadmin` |
| GET / PUT / DELETE | `/api/rooms/{id}` | `auth` |
| POST | `/api/rooms/{id}/photos` | `auth` |
| DELETE | `/api/room-photos/{id}` | `auth` |
| POST | `/api/rooms/{id}/status` | `auth` |

### Réservations

| Méthode | Route | Middleware |
|---|---|---|
| GET / POST | `/api/bookings` | `auth` |
| GET / PUT / DELETE | `/api/bookings/{id}` | `auth` |
| POST | `/api/bookings/{id}/checkin` | `auth` |
| POST | `/api/bookings/{id}/checkout` | `auth` |
| GET | `/api/planning` | `auth` |

### Clients

| Méthode | Route | Middleware |
|---|---|---|
| GET | `/api/clients` | `auth` |
| GET / PUT | `/api/clients/{id}` | `auth` |
| DELETE | `/api/clients/{id}` | `auth`, `role:owner\|superadmin` |

### Factures & Paiements

| Méthode | Route | Middleware |
|---|---|---|
| GET / POST | `/api/invoices` | `auth` |
| GET / PUT | `/api/invoices/{id}` | `auth` |
| GET | `/api/invoices/{id}/pdf` | `auth` |
| GET / POST | `/api/payments` | `auth` |
| PUT | `/api/payments/{id}` | `auth` |

### Dépenses

| Méthode | Route | Middleware |
|---|---|---|
| GET / POST | `/api/expenses` | `auth` |
| PUT / DELETE | `/api/expenses/{id}` | `auth` |

### Abonnements

| Méthode | Route | Middleware |
|---|---|---|
| GET | `/api/subscriptions/plans` | — |
| GET | `/api/subscriptions/status` | `auth` |
| POST | `/api/subscriptions/initiate` | `auth` |
| POST | `/api/subscriptions/callback` | — (webhook GeniusPay) |
| GET | `/api/subscriptions/verify/{ref}` | `auth` |

### Dashboard

| Méthode | Route | Middleware | Param requis |
|---|---|---|---|
| GET | `/api/dashboard/stats` | `auth` | `?establishment_id=` |
| GET | `/api/dashboard/planning` | `auth` | `?establishment_id=` |

### Vitrine publique (sans auth)

| Méthode | Route | Description |
|---|---|---|
| GET | `/api/public/search` | Recherche d'établissements/chambres |
| GET | `/api/public/establishments` | Liste des établissements publics |
| GET | `/api/public/property/{id}` | Détail d'un établissement |
| GET | `/api/public/rooms/{id}` | Détail d'une chambre |
| GET | `/api/public/availability/{id}` | Disponibilités d'une chambre |
| GET | `/api/public/destinations` | Destinations disponibles |
| POST | `/api/public/booking` | Créer une réservation en ligne |

---

## Sécurité

| Mécanisme | Détail |
|---|---|
| **Authentification** | JWT Bearer HS256, stocké en `localStorage` côté client |
| **Autorisation** | Middleware vérifie le rôle ; Guard isole les données par `establishment_id` |
| **Mots de passe** | `password_hash()` / `password_verify()` bcrypt |
| **Multi-tenant** | Chaque requête filtrée par `establishment_id` de l'utilisateur connecté |
| **Webhook GeniusPay** | Signature HMAC-SHA256 validée ; absence de secret en production = rejet |
| **Production** | `JWT_SECRET` obligatoire, erreurs PHP masquées, logs serveur activés |
| **CSRF** | Pas de cookie d'auth → pas de CSRF possible sur l'API REST |
| **Header Authorization** | Transmis à PHP via `RewriteRule ^ - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]` (nécessaire en mode mod_fcgid) |
