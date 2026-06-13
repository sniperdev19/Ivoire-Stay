<?php

namespace Core;

use Services\AuthService;

class Middleware
{
    public function __construct(
        private Request $request,
        private array   $middleware
    ) {}

    public function handle(): bool
    {
        foreach ($this->middleware as $mw) {
            if ($mw === 'auth') {
                if (!$this->checkAuth()) return false;
            } elseif (str_starts_with($mw, 'role:')) {
                $roles = explode('|', substr($mw, 5));
                if (!$this->checkRole($roles)) return false;
            }
        }
        return true;
    }

    private function checkAuth(): bool
    {
        $token = $this->request->bearerToken();

        if (!$token) {
            // For HTML pages, redirect to login
            if (!$this->request->isApi()) {
                Response::redirect(APP_URL . '/login');
            }
            Response::unauthorized('Token manquant');
        }

        $payload = AuthService::decode($token);
        if (!$payload) {
            if (!$this->request->isApi()) {
                Response::redirect(APP_URL . '/login');
            }
            Response::unauthorized('Token invalide ou expiré');
        }

        // Store user in globals for controllers
        $_REQUEST['_user'] = $payload;
        return true;
    }

    private function checkRole(array $roles): bool
    {
        $user = $_REQUEST['_user'] ?? null;
        if (!$user || !in_array($user['role'], $roles, true)) {
            Response::forbidden('Accès non autorisé pour ce rôle');
        }
        return true;
    }
}
