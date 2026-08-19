<?php

namespace Core;

class Response
{
    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success(mixed $data = null, string $message = 'OK', int $status = 200): never
    {
        self::json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    public static function error(string $message, int $status = 400, mixed $errors = null): never
    {
        self::json(['success' => false, 'message' => $message, 'errors' => $errors], $status);
    }

    public static function render(string $template, array $data = []): never
    {
        // Disponible dans tous les templates (peut être surchargé via $data)
        $base_url = APP_URL;
        extract($data);
        $templatePath = SRC_PATH . '/templates/' . $template . '.php';

        if (!file_exists($templatePath)) {
            http_response_code(404);
            echo '<h1>Template not found: ' . htmlspecialchars($template) . '</h1>';
            exit;
        }

        ob_start();
        require $templatePath;
        $content = ob_get_clean();

        if (str_starts_with($template, 'saas/') && !in_array($template, ['saas/login', 'saas/register', 'saas/install', 'saas/forgot-password', 'saas/reset-password', 'saas/verify-email', 'saas/team-invite'])) {
            require SRC_PATH . '/templates/saas/layout.php';
        } elseif (str_starts_with($template, 'admin/')) {
            require SRC_PATH . '/templates/admin/layout.php';
        } elseif (str_starts_with($template, 'vitrine/')) {
            require SRC_PATH . '/templates/vitrine/layout.php';
        } else {
            echo $content;
        }
        exit;
    }

    public static function redirect(string $url, int $status = 302): never
    {
        header('Location: ' . $url, true, $status);
        exit;
    }

    public static function notFound(string $message = 'Not Found'): never
    {
        self::error($message, 404);
    }

    public static function unauthorized(string $message = 'Unauthorized'): never
    {
        self::error($message, 401);
    }

    public static function forbidden(string $message = 'Forbidden'): never
    {
        self::error($message, 403);
    }
}
