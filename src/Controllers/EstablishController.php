<?php

namespace Controllers;

use Core\{Request, Response, Database, Guard};
use Models\Establishment;
use Services\UploadService;

class EstablishController
{
    public function index(Request $req, array $params = []): void
    {
        $user = $_REQUEST['_user'];
        Response::success(Establishment::forUser($user));
    }

    public function store(Request $req, array $params = []): void
    {
        $data = $req->all();
        $user = $_REQUEST['_user'];

        $required = ['name', 'type', 'city'];
        foreach ($required as $f) {
            if (empty($data[$f])) Response::error("Champ requis : $f");
        }

        // Seul un superadmin peut créer un établissement pour le compte d'autrui
        $ownerId = (Guard::isSuperadmin() && !empty($data['owner_id']))
            ? (int) $data['owner_id']
            : (int) $user['id'];

        $id = Establishment::create([
            'owner_id'    => $ownerId,
            'name'        => $data['name'],
            'type'        => $data['type'],
            'address'     => $data['address'] ?? null,
            'city'        => $data['city'],
            'phone'       => $data['phone'] ?? null,
            'description' => $data['description'] ?? null,
            'plan'        => $data['plan'] ?? 'starter',
            'is_active'   => 1,
        ]);

        Response::success(Establishment::find($id), 'Établissement créé', 201);
    }

    public function show(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        Guard::requireEstablishment($id);
        $estab = Establishment::withStats($id);
        if (!$estab) Response::notFound('Établissement introuvable');
        Response::success($estab);
    }

    public function update(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        Guard::requireEstablishment($id);
        if (!Establishment::find($id)) Response::notFound();

        $data    = $req->all();
        $allowed = ['name','type','address','city','phone','description','is_active'];
        // Seul un superadmin peut changer le plan directement (le reste passe par l'abonnement)
        if (Guard::isSuperadmin()) $allowed[] = 'plan';
        $update  = array_intersect_key($data, array_flip($allowed));
        Establishment::update($id, $update);
        Response::success(Establishment::find($id), 'Établissement mis à jour');
    }

    public function destroy(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        if (!Establishment::find($id)) Response::notFound();
        Establishment::update($id, ['is_active' => 0]);
        Response::success(null, 'Établissement désactivé');
    }

    public function uploadPhoto(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        Guard::requireEstablishment($id);
        $estab = Establishment::find($id);
        if (!$estab) Response::notFound('Établissement introuvable');

        $file = $req->file('photo');
        if (!$file) Response::error('Aucun fichier envoyé');

        try {
            // Delete old cover file if exists
            if ($estab['cover_photo']) {
                $old = UPLOAD_PATH . '/../' . $estab['cover_photo'];
                if (file_exists($old)) @unlink($old);
            }

            $path = UploadService::uploadEstabPhoto($file, $id);
            Database::query("UPDATE establishments SET cover_photo = ? WHERE id = ?", [$path, $id]);
            Response::success(['path' => $path], 'Photo mise à jour', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage());
        }
    }
}
