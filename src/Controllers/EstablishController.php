<?php

namespace Controllers;

use Core\{Request, Response, Database, Guard, PlanGate};
use Models\{Establishment, AgentEstablishment};
use Services\UploadService;

class EstablishController
{
    /** N'accepte qu'un format HH:MM ou HH:MM:SS (celui envoyé par <input type="time">), sinon la valeur par défaut. */
    private function sanitizeTime(?string $value, string $default): string
    {
        if ($value && preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $value)) {
            return strlen($value) === 5 ? $value . ':00' : $value;
        }
        return $default;
    }

    public function index(Request $req, array $params = []): void
    {
        $user = $_REQUEST['_user'];
        Response::success(Establishment::forUser($user));
    }

    /** GET /api/establishment/qr — jeton "Mon QR code" scanné par les agents commerciaux. */
    public function qr(Request $req, array $params = []): void
    {
        $estabId = Guard::resolveEstabId($req);
        if (!$estabId) Response::error('establishment_id requis');
        Guard::requireEstablishment($estabId);

        $estab = Establishment::find($estabId);
        if (!$estab) Response::notFound('Établissement introuvable');

        // Premier scan gagne, jamais de réassignation (cf. AgentController::scanQr) —
        // une fois rattaché, le QR n'a plus aucune utilité : le front le désactive
        // (affiche "déjà rattaché" au lieu du code) plutôt que de continuer à
        // afficher un code qui semble actif mais qu'un nouveau scan rejetterait.
        $agentLinked = AGENTS_ENABLED && (bool) AgentEstablishment::findByEstablishment($estabId);

        Response::success(['qr_token' => $estab['qr_token'], 'agent_linked' => $agentLinked]);
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

        // Limite d'établissements par abonnement : seul le plan Business (multi_estab)
        // autorise plus d'un établissement par propriétaire.
        $existing = Establishment::findByOwner($ownerId);
        if (!Guard::isSuperadmin() && !PlanGate::canAddEstablishment($existing, count($existing))) {
            Response::json([
                'success'          => false,
                'upgrade_required' => true,
                'feature'          => 'multi_estab',
                'message'          => "Votre abonnement ne permet qu'un seul établissement. Passez au plan Premium+ pour en gérer plusieurs.",
            ], 403);
            exit;
        }

        // Le plan ne se change jamais à la création : il démarre toujours en Starter
        // et ne progresse que via le flux d'abonnement (paiement confirmé), ou par un
        // superadmin via update(). Accepter `plan` depuis le client ici serait une
        // élévation de privilège (établissement premium gratuit).
        $plan = (Guard::isSuperadmin() && !empty($data['plan'])) ? $data['plan'] : 'starter';

        $id = Establishment::create([
            'owner_id'    => $ownerId,
            'name'        => $data['name'],
            'type'        => $data['type'],
            'address'     => $data['address'] ?? null,
            'city'        => $data['city'],
            'phone'       => $data['phone'] ?? null,
            'email'       => $data['email'] ?? null,
            'website'     => $data['website'] ?? null,
            'latitude'    => isset($data['latitude'])  ? (float) $data['latitude']  : null,
            'longitude'   => isset($data['longitude']) ? (float) $data['longitude'] : null,
            'description' => $data['description'] ?? null,
            'plan'        => $plan,
            'is_active'   => 1,
            'check_in_time'  => $this->sanitizeTime($data['check_in_time']  ?? null, '14:00:00'),
            'check_out_time' => $this->sanitizeTime($data['check_out_time'] ?? null, '12:00:00'),
        ]);

        Establishment::update($id, ['slug' => Establishment::generateSlug($data['name'], $id)]);

        Response::success(Establishment::find($id), 'Établissement créé', 201);
    }

    public function show(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        Guard::requireEstablishment($id);
        $estab = Establishment::withStats($id);
        if (!$estab) Response::notFound('Établissement introuvable');
        $estab['photos'] = Establishment::photos($id);
        Response::success($estab);
    }

    public function update(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        Guard::requireEstablishment($id);
        $estab = Establishment::find($id);
        if (!$estab) Response::notFound();

        $data    = $req->all();
        $allowed = ['name','type','address','city','phone','email','website','latitude','longitude','description','is_active','check_in_time','check_out_time'];
        // Seul un superadmin peut changer le plan directement (le reste passe par l'abonnement)
        if (Guard::isSuperadmin()) $allowed[] = 'plan';
        // Désactiver le paiement en ligne est réservé aux plans qui débloquent ce contrôle —
        // et de toute façon inopérant tant que ONLINE_PAYMENTS_ENABLED (verrou v1) est fermé :
        // pas la peine de laisser un owner activer un réglage qui ne fera rien.
        if (ONLINE_PAYMENTS_ENABLED && PlanGate::can($estab, 'online_payment_control')) $allowed[] = 'online_payment_enabled';
        // Le boost (mise en avant dans la recherche publique) est réservé au plan Business
        if (PlanGate::can($estab, 'boost')) $allowed[] = 'is_boosted';
        $update  = array_intersect_key($data, array_flip($allowed));
        if (isset($update['check_in_time']))  $update['check_in_time']  = $this->sanitizeTime($update['check_in_time'],  $estab['check_in_time']);
        if (isset($update['check_out_time'])) $update['check_out_time'] = $this->sanitizeTime($update['check_out_time'], $estab['check_out_time']);
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

        $count = (int) Database::query(
            "SELECT COUNT(*) FROM establishment_photos WHERE establishment_id = ?", [$id]
        )->fetchColumn();
        if ($count >= 10) Response::error('Maximum 10 photos par établissement');

        $file = $req->file('photo');
        if (!$file) Response::error('Aucun fichier envoyé');

        try {
            $path    = UploadService::uploadEstabPhoto($file, $id);
            $isCover = (int) ($req->input('is_cover', 0) == '1') || $count === 0;

            if ($isCover) {
                Database::query("UPDATE establishment_photos SET is_cover = 0 WHERE establishment_id = ?", [$id]);
            }

            Database::query(
                "INSERT INTO establishment_photos (establishment_id, file_path, is_cover, sort_order) VALUES (?, ?, ?, ?)",
                [$id, $path, $isCover, $count]
            );

            if ($isCover) {
                Database::query("UPDATE establishments SET cover_photo = ? WHERE id = ?", [$path, $id]);
            }

            Response::success(['path' => $path], 'Photo ajoutée', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function destroyPhoto(Request $req, array $params = []): void
    {
        $id    = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $photo = Guard::requireEstablishmentPhoto($id);

        $fullPath = UPLOAD_PATH . '/../' . $photo['file_path'];
        if (file_exists($fullPath)) @unlink($fullPath);

        Database::query("DELETE FROM establishment_photos WHERE id = ?", [$id]);

        if ($photo['is_cover']) {
            $next = Database::query(
                "SELECT file_path FROM establishment_photos WHERE establishment_id = ? ORDER BY sort_order LIMIT 1",
                [$photo['establishment_id']]
            )->fetchColumn();
            Database::query(
                "UPDATE establishment_photos SET is_cover = 1 WHERE establishment_id = ? ORDER BY sort_order LIMIT 1",
                [$photo['establishment_id']]
            );
            Database::query("UPDATE establishments SET cover_photo = ? WHERE id = ?", [$next ?: null, $photo['establishment_id']]);
        }

        Response::success(null, 'Photo supprimée');
    }
}
