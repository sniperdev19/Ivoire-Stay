<?php

declare(strict_types=1);

// Bootstrap
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/config.php';

use Core\Router;

set_exception_handler(function (Throwable $e) {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $base = parse_url(APP_URL, PHP_URL_PATH) ?? '';
    $path = ($base && str_starts_with($uri, $base)) ? substr($uri, strlen($base)) : $uri;

    if (str_starts_with($path, '/api/')) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } else {
        http_response_code(500);
        echo '<h1>Erreur serveur</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    exit;
});

$router = new Router();

require_once dirname(__DIR__) . '/config/routes.php';

$router->dispatch();
