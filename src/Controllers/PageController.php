<?php

namespace Controllers;

use Core\{Request, Response};

class PageController
{
    // ─── SaaS Pages ───────────────────────────────────────────────────────────

    public function login(Request $req, array $params = []): void
    {
        Response::render('saas/login', ['title' => 'Connexion – Ivoire Stay']);
    }

    public function register(Request $req, array $params = []): void
    {
        Response::render('saas/register', ['title' => 'Inscription – Ivoire Stay']);
    }

    public function install(Request $req, array $params = []): void
    {
        Response::render('saas/install', ['title' => 'Installer l\'application – Ivoire Stay']);
    }

    public function saas(Request $req, array $params = []): void
    {
        Response::render('saas/dashboard', ['title' => 'Tableau de bord', 'page' => 'dashboard']);
    }

    public function planning(Request $req, array $params = []): void
    {
        Response::render('saas/planning', ['title' => 'Planning', 'page' => 'planning']);
    }

    public function rooms(Request $req, array $params = []): void
    {
        Response::render('saas/rooms', ['title' => 'Chambres & Tarifs', 'page' => 'rooms']);
    }

    public function bookings(Request $req, array $params = []): void
    {
        Response::render('saas/bookings', ['title' => 'Réservations', 'page' => 'bookings']);
    }

    public function clients(Request $req, array $params = []): void
    {
        Response::render('saas/clients', ['title' => 'Clients', 'page' => 'clients']);
    }

    public function invoices(Request $req, array $params = []): void
    {
        Response::render('saas/invoices', ['title' => 'Facturation', 'page' => 'invoices']);
    }

    public function payments(Request $req, array $params = []): void
    {
        Response::render('saas/payments', ['title' => 'Paiements', 'page' => 'payments']);
    }

    public function expenses(Request $req, array $params = []): void
    {
        Response::render('saas/expenses', ['title' => 'Dépenses', 'page' => 'expenses']);
    }

    public function reports(Request $req, array $params = []): void
    {
        Response::render('saas/reports', ['title' => 'Rapports', 'page' => 'reports']);
    }

    public function settings(Request $req, array $params = []): void
    {
        Response::render('saas/settings', ['title' => 'Paramètres', 'page' => 'settings']);
    }

    public function help(Request $req, array $params = []): void
    {
        Response::render('saas/help', ['title' => 'Centre d\'aide', 'page' => 'help']);
    }

    // ─── Vitrine Pages ────────────────────────────────────────────────────────

    public function home(Request $req, array $params = []): void
    {
        Response::render('vitrine/home', ['title' => 'Ivoire Stay – Trouvez votre hébergement en Côte d\'Ivoire']);
    }


    public function apropos(Request $req, array $params = []): void
    {
        Response::render('vitrine/apropos', ['title' => 'À propos – Ivoire Stay']);
    }

    public function contact(Request $req, array $params = []): void
    {
        Response::render('vitrine/contact', ['title' => 'Contact – Ivoire Stay']);
    }

    public function pricing(Request $req, array $params = []): void
    {
        Response::render('vitrine/pricing', ['title' => 'Tarifs – Ivoire Stay']);
    }

    public function search(Request $req, array $params = []): void
    {
        Response::render('vitrine/search', ['title' => 'Résultats de recherche']);
    }

    public function property(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        Response::render('vitrine/property', ['title' => 'Établissement', 'property_id' => $id]);
    }

    public function bookingPage(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        Response::render('vitrine/booking', ['title' => 'Réserver', 'room_id' => $id]);
    }
}
