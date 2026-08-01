<?php

namespace Controllers;

use Core\{Request, Response};

/**
 * Pages de l'espace admin plateforme (superadmin). Auth/role gérés côté JS
 * (public/assets/js/admin.js), comme pour les pages /saas/* — voir PageController.
 */
class AdminPageController
{
    public function dashboard(Request $req, array $params = []): void
    {
        Response::render('admin/dashboard', ['title' => 'AfriStay Admin — Vue d\'ensemble', 'page' => 'dashboard']);
    }

    public function establishments(Request $req, array $params = []): void
    {
        Response::render('admin/establishments', ['title' => 'AfriStay Admin — Établissements', 'page' => 'establishments']);
    }

    public function owners(Request $req, array $params = []): void
    {
        Response::render('admin/owners', ['title' => 'AfriStay Admin — Propriétaires', 'page' => 'owners']);
    }

    public function payouts(Request $req, array $params = []): void
    {
        Response::render('admin/payouts', ['title' => 'AfriStay Admin — Retraits', 'page' => 'payouts']);
    }

    /** Fonctionnalité temporaire — cf. AGENTS_ENABLED dans config/config.php. */
    public function agents(Request $req, array $params = []): void
    {
        Response::render('admin/agents', ['title' => 'AfriStay Admin — Agents commerciaux', 'page' => 'agents']);
    }

    public function settings(Request $req, array $params = []): void
    {
        Response::render('admin/settings', ['title' => 'AfriStay Admin — Paramètres', 'page' => 'settings']);
    }

    public function notifications(Request $req, array $params = []): void
    {
        Response::render('admin/notifications', ['title' => 'AfriStay Admin — Notifications', 'page' => 'notifications']);
    }

    public function contactMessages(Request $req, array $params = []): void
    {
        Response::render('admin/contact-messages', ['title' => 'AfriStay Admin — Messages de contact', 'page' => 'contact-messages']);
    }

    public function newsletter(Request $req, array $params = []): void
    {
        Response::render('admin/newsletter', ['title' => 'AfriStay Admin — Newsletter', 'page' => 'newsletter']);
    }

    public function announcements(Request $req, array $params = []): void
    {
        Response::render('admin/announcements', ['title' => 'AfriStay Admin — Annonces vitrine', 'page' => 'announcements']);
    }
}
