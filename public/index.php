<?php

declare(strict_types=1);

// Bootstrap
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/config.php';

use Core\Router;

// En-têtes de sécurité appliqués à toutes les réponses (JSON API et pages
// HTML). CSP autorise 'unsafe-inline'/'unsafe-eval' pour script-src : Alpine.js
// (non précompilé) en dépend pour évaluer x-data/x-show/@click. Le reste
// (frame-ancestors, object-src, connect-src...) reste restreint à l'origine.
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), camera=(), microphone=()');
// img-src inclut blob: pour l'aperçu de photo avant upload (URL.createObjectURL
// dans saas-rooms.js) — ce sont des objets locaux créés par le navigateur
// lui-même, pas des ressources tierces.
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; object-src 'none'; base-uri 'self'; form-action 'self'");
if (($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

set_exception_handler(function (Throwable $e) {
    error_log('Uncaught exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $base = parse_url(APP_URL, PHP_URL_PATH) ?? '';
    $path = ($base && str_starts_with($uri, $base)) ? substr($uri, strlen($base)) : $uri;

    // Le détail de l'exception (requêtes SQL, chemins serveur...) ne doit
    // jamais atteindre le client en production : seul le log serveur le voit.
    $detail = (defined('APP_ENV') && APP_ENV === 'development')
        ? $e->getMessage()
        : 'Une erreur est survenue. Veuillez réessayer.';

    if (str_starts_with($path, '/api/')) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $detail]);
    } else {
        http_response_code(500);
        echo '<h1>Erreur serveur</h1><p>' . htmlspecialchars($detail) . '</p>';
    }
    exit;
});

$router = new Router();

require_once dirname(__DIR__) . '/config/routes.php';

$router->dispatch();