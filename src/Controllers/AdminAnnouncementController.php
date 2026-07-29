<?php

namespace Controllers;

use Core\{Request, Response, Database};
use Models\Announcement;

/**
 * Annonces affichées en bandeau sur la vitrine publique (maintenance,
 * nouveauté...). Une seule active à la fois PAR CONSTRUCTION (pas juste "la
 * plus récente gagne") : activer une annonce désactive systématiquement
 * toutes les autres, pour que le statut affiché dans /admin/announcements
 * corresponde exactement à ce que la vitrine montre — sinon deux lignes
 * "Active" simultanées induiraient en erreur (une seule s'affiche réellement,
 * cf. Announcement::activeOne()).
 */
class AdminAnnouncementController
{
    public function index(Request $req, array $params = []): void
    {
        Response::success(Announcement::allRecent());
    }

    public function store(Request $req, array $params = []): void
    {
        $title   = trim((string) $req->input('title', ''));
        $message = trim((string) $req->input('message', ''));
        if ($title === '') Response::error('Le titre est requis');
        if ($message === '') Response::error('Le message est requis');

        Database::query('UPDATE announcements SET is_active = 0');
        $id = Announcement::create([
            'title'     => $title,
            'message'   => $message,
            'is_active' => 1,
        ]);

        Response::success(Announcement::find($id), 'Annonce créée', 201);
    }

    public function update(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $announcement = Announcement::find($id);
        if (!$announcement) Response::notFound('Annonce introuvable');

        $data    = $req->all();
        $allowed = ['title', 'message', 'is_active'];
        $update  = array_intersect_key($data, array_flip($allowed));
        if (isset($update['title'])   && trim((string) $update['title'])   === '') Response::error('Le titre est requis');
        if (isset($update['message']) && trim((string) $update['message']) === '') Response::error('Le message est requis');

        if (!empty($update['is_active'])) {
            Database::query('UPDATE announcements SET is_active = 0 WHERE id != ?', [$id]);
        }
        Announcement::update($id, $update);
        Response::success(Announcement::find($id), 'Annonce mise à jour');
    }

    public function destroy(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        if (!Announcement::find($id)) Response::notFound('Annonce introuvable');
        Announcement::delete($id);
        Response::success(null, 'Annonce supprimée');
    }
}
