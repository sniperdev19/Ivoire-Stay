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
}
