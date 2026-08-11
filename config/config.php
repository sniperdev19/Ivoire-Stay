<?php

// Load .env
$envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
        putenv(trim($key) . '=' . trim($value));
    }
}

function env(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

define('APP_ENV',   env('APP_ENV', 'production'));
define('APP_URL',   env('APP_URL', 'http://localhost'));
define('BASE_PATH', dirname(__DIR__));
define('SRC_PATH',  BASE_PATH . '/src');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('UPLOAD_PATH',  BASE_PATH . '/public/assets/uploads');

define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'hotel_sync'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

define('JWT_SECRET', env('JWT_SECRET', 'changeme'));
define('JWT_EXPIRY', (int) env('JWT_EXPIRY', 86400));

define('UPLOAD_MAX_SIZE', (int) env('UPLOAD_MAX_SIZE', 5242880));

define('MAIL_HOST',      env('MAIL_HOST',      'smtp.gmail.com'));
define('MAIL_PORT',      env('MAIL_PORT',      '587'));
define('MAIL_USER',      env('MAIL_USER',      ''));
define('MAIL_PASS',      env('MAIL_PASS',      ''));
define('MAIL_FROM',      env('MAIL_FROM',      env('MAIL_USER', '')));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'Afristay'));

define('GENIUS_PAY_KEY',            env('GENIUS_PAY_KEY',    ''));
define('GENIUS_PAY_SECRET',         env('GENIUS_PAY_SECRET', ''));
define('GENIUS_PAY_URL',            env('GENIUS_PAY_URL',    'http://pay.genius.ci/api/v1/merchant'));
define('GENIUS_PAY_WEBHOOK_SECRET', env('GENIUS_PAY_WEBHOOK_SECRET', ''));

// Verrou global v1 : le paiement en ligne des réservations (GeniusPay) reste
// désactivé partout (front ET back) tant que ce n'est pas false→true
// explicitement en environnement de production, le temps de valider le flux
// complet (initiation, webhook, commission, retrait) en conditions réelles.
// Ne pas confondre avec `online_payment_enabled` (colonne establishments,
// réglage par établissement — forcé à 1 et non désactivable sur le plan
// Starter, togglable sur Pro/Business) ou le plan-gate `online_payment`
// (config/plans.php, accès de base, tous les plans) / `online_payment_control`
// (capacité à désactiver soi-même, Pro/Business uniquement) : les trois
// restent inopérants tant que ce verrou global est fermé. Une fois ouvert,
// le plan Starter a le paiement en ligne actif et commissionné par défaut
// (voir Services\PlanPricingService::commissionPct()).
define('ONLINE_PAYMENTS_ENABLED', filter_var(env('ONLINE_PAYMENTS_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN));

// Fonctionnalité temporaire : espace agents commerciaux (inscription/scan QR/
// commissions). Coupe les routes /agent/* et le bouton "Mon QR code" côté
// établissement tant que false, sans supprimer le code.
// Pilotable depuis /admin/settings (Core\Settings, table platform_settings) —
// la variable d'environnement ne sert plus que de valeur par défaut tant
// qu'aucun réglage n'a été enregistré en base (ou si la table n'existe pas
// encore sur cet environnement, cf. Core\Settings::get()). L'autoloader est
// déjà chargé à ce stade (voir public/index.php), Core\Settings est donc
// disponible ici.
define('AGENTS_ENABLED', \Core\Settings::getBool('agents_enabled', filter_var(env('AGENTS_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN)));

define('VAPID_PUBLIC_KEY',  env('VAPID_PUBLIC_KEY',  ''));
define('VAPID_PRIVATE_KEY', env('VAPID_PRIVATE_KEY', ''));
define('VAPID_SUBJECT',     env('VAPID_SUBJECT',     'mailto:contact@example.com'));

// Google Analytics (GA4) : identifiant de mesure (G-XXXXXXXXXX). Vide tant que
// non configuré dans .env — le layout vitrine n'injecte alors aucun script.
define('GA_MEASUREMENT_ID', env('GA_MEASUREMENT_ID', ''));

// Error reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');

    // En production, refuser de démarrer avec des secrets par défaut :
    // un JWT_SECRET connu permettrait de forger n'importe quel jeton.
    if (JWT_SECRET === 'changeme' || JWT_SECRET === '') {
        http_response_code(500);
        error_log('SECURITY: JWT_SECRET non configuré en production');
        die(json_encode(['error' => 'Configuration serveur invalide']));
    }
}
