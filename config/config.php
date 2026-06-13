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

define('GENIUS_PAY_KEY',            env('GENIUS_PAY_KEY', ''));
define('GENIUS_PAY_URL',            env('GENIUS_PAY_URL', 'https://api.geniuspay.ci/v1'));
define('GENIUS_PAY_WEBHOOK_SECRET', env('GENIUS_PAY_WEBHOOK_SECRET', ''));

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
