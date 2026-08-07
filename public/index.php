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
// geolocation=(self) : la page Paramètres l'utilise (bouton "Localiser mon
// établissement", saas-settings.js::locateMe()) — bloqué en tiers/iframe,
// autorisé en premier contexte. camera=(self) : scan QR agent commercial
// (agent-dashboard.js::openScanner(), getUserMedia) — était à camera=()
// avant l'ajout de cette fonctionnalité, ce qui bloquait silencieusement
// toute ouverture de caméra (getUserMedia rejette, cameraError=true, repli
// sur la saisie manuelle du code). microphone reste bloqué, rien ne l'utilise.
header('Permissions-Policy: geolocation=(self), camera=(self), microphone=()');
// img-src inclut blob: pour l'aperçu de photo avant upload (URL.createObjectURL
// dans saas-rooms.js) — ce sont des objets locaux créés par le navigateur
// lui-même, pas des ressources tierces. *.tile.openstreetmap.org : tuiles de
// la carte Leaflet (property.js, vitrine + emplacement établissement).
// connect-src inclut router.project-osrm.org : service de routage public
// (gratuit, sans clé) utilisé par property.js::drawRoute() pour tracer
// l'itinéraire client → établissement — pas de SLA officiel, à remplacer par
// un fournisseur payant si le volume devient significatif.
// script-src/connect-src incluent googletagmanager.com/google-analytics.com :
// tracking Google Analytics (GA4, vitrine/layout.php) — n'émet aucune requête
// tant que GA_MEASUREMENT_ID (.env) est vide.
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.googletagmanager.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob: https://*.tile.openstreetmap.org; font-src 'self' data:; connect-src 'self' https://router.project-osrm.org https://www.google-analytics.com https://www.googletagmanager.com; frame-ancestors 'none'; object-src 'none'; base-uri 'self'; form-action 'self'");
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