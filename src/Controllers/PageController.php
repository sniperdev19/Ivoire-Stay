<?php

namespace Controllers;

use Core\{Request, Response};
use Models\{Establishment, Room};

class PageController
{
    // ─── SaaS Pages ───────────────────────────────────────────────────────────

    public function login(Request $req, array $params = []): void
    {
        Response::render('saas/login', ['title' => 'Connexion – Afristay']);
    }

    public function register(Request $req, array $params = []): void
    {
        Response::render('saas/register', ['title' => 'Inscription – Afristay']);
    }

    public function install(Request $req, array $params = []): void
    {
        Response::render('saas/install', ['title' => 'Installer l\'application – Afristay']);
    }

    // ─── Espace agents commerciaux (fonctionnalité temporaire, cf. AGENTS_ENABLED) ──

    public function agentRegister(Request $req, array $params = []): void
    {
        Response::render('agent/register', ['title' => 'Devenir agent – Afristay']);
    }

    public function agentLogin(Request $req, array $params = []): void
    {
        Response::render('agent/login', ['title' => 'Connexion agent – Afristay']);
    }

    public function agentDashboard(Request $req, array $params = []): void
    {
        Response::render('agent/dashboard', ['title' => 'Tableau de bord agent – Afristay', 'page' => 'dashboard']);
    }

    public function agentProfile(Request $req, array $params = []): void
    {
        Response::render('agent/profile', ['title' => 'Mon profil agent – Afristay', 'page' => 'profile']);
    }

    public function agentProspects(Request $req, array $params = []): void
    {
        Response::render('agent/prospects', ['title' => 'Mes prospects – Afristay', 'page' => 'prospects']);
    }

    public function forgotPassword(Request $req, array $params = []): void
    {
        Response::render('saas/forgot-password', ['title' => 'Mot de passe oublié – Afristay']);
    }

    public function resetPassword(Request $req, array $params = []): void
    {
        Response::render('saas/reset-password', ['title' => 'Réinitialiser le mot de passe – Afristay']);
    }

    public function verifyEmail(Request $req, array $params = []): void
    {
        Response::render('saas/verify-email', ['title' => 'Vérification email – Afristay']);
    }

    public function teamInviteAccept(Request $req, array $params = []): void
    {
        Response::render('saas/team-invite', ['title' => 'Invitation d\'équipe – Afristay']);
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
        Response::render('saas/billing', ['title' => 'Comptabilité', 'page' => 'billing', 'defaultTab' => 'invoices']);
    }

    public function payments(Request $req, array $params = []): void
    {
        Response::render('saas/billing', ['title' => 'Comptabilité', 'page' => 'billing', 'defaultTab' => 'payments']);
    }

    public function expenses(Request $req, array $params = []): void
    {
        Response::render('saas/expenses', ['title' => 'Dépenses', 'page' => 'expenses']);
    }

    public function payouts(Request $req, array $params = []): void
    {
        Response::render('saas/payouts', ['title' => 'Retraits', 'page' => 'payouts']);
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

    public function docs(Request $req, array $params = []): void
    {
        Response::render('saas/docs', ['title' => 'Documentation', 'page' => 'help']);
    }

    public function checkout(Request $req, array $params = []): void
    {
        Response::render('saas/checkout', ['title' => 'Paiement abonnement – Afristay']);
    }

    // ─── Vitrine Pages ────────────────────────────────────────────────────────

    public function home(Request $req, array $params = []): void
    {
        Response::render('vitrine/home', [
            'title' => 'Afristay – Trouvez votre hébergement en Côte d\'Ivoire',
            'stats' => Establishment::platformStats(),
        ]);
    }


    public function apropos(Request $req, array $params = []): void
    {
        Response::render('vitrine/apropos', ['title' => 'À propos – Afristay']);
    }

    public function contact(Request $req, array $params = []): void
    {
        Response::render('vitrine/contact', ['title' => 'Contact – Afristay']);
    }

    public function pricing(Request $req, array $params = []): void
    {
        Response::render('vitrine/pricing', ['title' => 'Tarifs – Afristay']);
    }

    public function terms(Request $req, array $params = []): void
    {
        Response::render('vitrine/cgu', ['title' => 'Conditions générales d\'utilisation – Afristay']);
    }

    public function privacy(Request $req, array $params = []): void
    {
        Response::render('vitrine/confidentialite', ['title' => 'Politique de confidentialité – Afristay']);
    }

    public function search(Request $req, array $params = []): void
    {
        Response::render('vitrine/search', ['title' => 'Résultats de recherche']);
    }

    public function property(Request $req, array $params = []): void
    {
        $slug  = (string) ($params['slug'] ?? $_GET['_route_slug'] ?? '');
        $estab = $slug !== '' ? Establishment::findBySlug($slug) : null;
        $id    = $estab['id'] ?? 0;
        Response::render('vitrine/property', ['title' => 'Établissement', 'property_id' => $id]);
    }

    public function bookingPage(Request $req, array $params = []): void
    {
        $slug = (string) ($params['slug'] ?? $_GET['_route_slug'] ?? '');
        $room = $slug !== '' ? Room::findBySlug($slug) : null;
        $id   = $room['id'] ?? 0;
        Response::render('vitrine/booking', ['title' => 'Réserver', 'room_id' => $id]);
    }

    public function myBookings(Request $req, array $params = []): void
    {
        Response::render('vitrine/mes-reservations', ['title' => 'Mes réservations – Afristay']);
    }

    public function newsletterUnsubscribe(Request $req, array $params = []): void
    {
        Response::render('vitrine/newsletter-unsubscribe', ['title' => 'Désabonnement newsletter – Afristay']);
    }
}
