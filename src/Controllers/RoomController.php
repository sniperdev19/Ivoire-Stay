<?php

namespace Controllers;

use Core\{Request, Response, Database, PlanGate, Guard};
use Models\{Room, RoomType, Establishment};
use Services\UploadService;

class RoomController
{
    private function estabId(Request $req): int
    {
        return Guard::resolveEstabId($req);
    }

    // ─── Room Types ───────────────────────────────────────────────────────────

    public function indexTypes(Request $req, array $params = []): void
    {
        $estabId = $this->estabId($req);
        if (!$estabId) Response::error('establishment_id requis');
        Response::success(RoomType::withRoomCount($estabId));
    }

    public function storeType(Request $req, array $params = []): void
    {
        $data    = $req->all();
        $estabId = $this->estabId($req);
        if (!$estabId) Response::error('establishment_id requis');

        $required = ['name', 'base_price', 'capacity'];
        foreach ($required as $f) {
            if (empty($data[$f])) Response::error("Champ requis : $f");
        }

        $id = RoomType::create([
            'establishment_id'   => $estabId,
            'name'               => $data['name'],
            'description'        => $data['description'] ?? null,
            'capacity'           => (int) $data['capacity'],
            'base_price'         => (float) $data['base_price'],
            'weekend_price'  => isset($data['weekend_price']) ? (float)$data['weekend_price'] : null,
            'passage_price'  => isset($data['passage_price']) ? (float)$data['passage_price'] : null,
        ]);

        Response::success(RoomType::find($id), 'Type de chambre créé', 201);
    }

    public function updateType(Request $req, array $params = []): void
    {
        $id   = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $data = $req->all();
        Guard::requireRoomType($id);

        $allowed = ['name','description','capacity','base_price','weekend_price','passage_price'];
        $update  = array_intersect_key($data, array_flip($allowed));

        RoomType::update($id, $update);
        Response::success(RoomType::find($id), 'Type mis à jour');
    }

    public function destroyType(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        Guard::requireRoomType($id);

        $hasRooms = (int) \Core\Database::query(
            "SELECT COUNT(*) FROM rooms WHERE room_type_id = ?", [$id]
        )->fetchColumn();

        if ($hasRooms > 0) {
            Response::error('Impossible de supprimer un type qui possède des chambres', 409);
        }

        try {
            RoomType::delete($id);
            Response::success(null, 'Type supprimé');
        } catch (\PDOException $e) {
            Response::error('Suppression impossible : ce type est lié à des données existantes', 409);
        }
    }

    // ─── Rooms ────────────────────────────────────────────────────────────────

    public function index(Request $req, array $params = []): void
    {
        $estabId = $this->estabId($req);
        if (!$estabId) Response::error('establishment_id requis');
        Response::success(Room::allWithDetails($estabId));
    }

    public function store(Request $req, array $params = []): void
    {
        $data    = $req->all();
        $estabId = $this->estabId($req);
        if (!$estabId) Response::error('establishment_id requis');

        // Plan gate: room limit for starter
        $estab       = Establishment::find($estabId);
        $currentCount = (int) Database::query("SELECT COUNT(*) FROM rooms WHERE establishment_id = ?", [$estabId])->fetchColumn();
        if (!PlanGate::canAddRoom($estab ?? [], $currentCount)) {
            $max = PlanGate::maxRooms($estab ?? []);
            Response::json([
                'success'          => false,
                'upgrade_required' => true,
                'feature'          => 'rooms',
                'message'          => "Limite de $max chambres atteinte avec le plan Gratuit. Passez au plan Premium pour des chambres illimitées.",
            ], 403);
            exit;
        }

        $required = ['room_type_id', 'number'];
        foreach ($required as $f) {
            if (empty($data[$f])) Response::error("Champ requis : $f");
        }

        // Le type de chambre doit appartenir au même établissement
        $type = RoomType::find((int) $data['room_type_id']);
        if (!$type || (int) $type['establishment_id'] !== $estabId) {
            Response::error('Type de chambre invalide pour cet établissement');
        }

        $id = Room::create([
            'establishment_id' => $estabId,
            'room_type_id'     => (int) $data['room_type_id'],
            'number'           => $data['number'],
            'floor'            => (int) ($data['floor'] ?? 0),
            'status'           => $data['status'] ?? 'available',
            'notes'            => $data['notes'] ?? null,
        ]);

        // Save amenities
        if (!empty($data['amenities']) && is_array($data['amenities'])) {
            foreach ($data['amenities'] as $name) {
                Database::query(
                    "INSERT INTO room_amenities (room_id, name, available) VALUES (?, ?, 1)",
                    [$id, $name]
                );
            }
        }

        Response::success(Room::findWithDetails($id), 'Chambre créée', 201);
    }

    public function show(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        Guard::requireRoom($id);
        Response::success(Room::findWithDetails($id));
    }

    public function update(Request $req, array $params = []): void
    {
        $id   = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $room = Guard::requireRoom($id);

        $data    = $req->all();
        $allowed = ['room_type_id','number','floor','status','notes'];
        $update  = array_intersect_key($data, array_flip($allowed));
        if (!empty($update)) Room::update($id, $update);

        // Update amenities if provided
        if (isset($data['amenities']) && is_array($data['amenities'])) {
            Database::query("DELETE FROM room_amenities WHERE room_id = ?", [$id]);
            foreach ($data['amenities'] as $name) {
                Database::query(
                    "INSERT INTO room_amenities (room_id, name, available) VALUES (?, ?, 1)",
                    [$id, $name]
                );
            }
        }

        Response::success(Room::findWithDetails($id), 'Chambre mise à jour');
    }

    public function destroy(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        Guard::requireRoom($id);

        // Block only if someone is currently checked in
        $checkedIn = (int) Database::query(
            "SELECT COUNT(*) FROM bookings WHERE room_id = ? AND status = 'checked_in'",
            [$id]
        )->fetchColumn();

        if ($checkedIn > 0) {
            Response::error('Impossible de supprimer une chambre avec un séjour en cours', 409);
        }

        try {
            // Cascade-delete linked data before removing the room
            $bookingIds = Database::query(
                "SELECT id FROM bookings WHERE room_id = ?", [$id]
            )->fetchAll(\PDO::FETCH_COLUMN);

            if ($bookingIds) {
                $in = implode(',', array_map('intval', $bookingIds));
                Database::query("DELETE FROM payments WHERE booking_id IN ($in)");
                Database::query("DELETE FROM invoices WHERE booking_id IN ($in)");
                Database::query("DELETE FROM bookings WHERE room_id = ?", [$id]);
            }

            Database::query("DELETE FROM room_photos   WHERE room_id = ?", [$id]);
            Database::query("DELETE FROM room_amenities WHERE room_id = ?", [$id]);
            Room::delete($id);
            Response::success(null, 'Chambre supprimée');
        } catch (\PDOException $e) {
            Response::error('Suppression impossible : ' . $e->getMessage(), 409);
        }
    }

    public function uploadPhoto(Request $req, array $params = []): void
    {
        $id   = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $room = Guard::requireRoom($id);

        $file = $req->file('photo');
        if (!$file) Response::error('Aucun fichier envoyé');

        try {
            $path    = UploadService::uploadRoomPhoto($file, $id);
            $isCover = (int) ($req->input('is_cover', 0) == '1');

            if ($isCover) {
                Database::query("UPDATE room_photos SET is_cover = 0 WHERE room_id = ?", [$id]);
            }

            Database::query(
                "INSERT INTO room_photos (room_id, file_path, is_cover, sort_order) VALUES (?, ?, ?, ?)",
                [$id, $path, $isCover, 0]
            );

            Response::success(['path' => $path], 'Photo ajoutée', 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function destroyPhoto(Request $req, array $params = []): void
    {
        $id    = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $photo = Guard::requireRoomPhoto($id);

        // Delete physical file
        $fullPath = UPLOAD_PATH . '/../' . $photo['file_path'];
        if (file_exists($fullPath)) @unlink($fullPath);

        Database::query("DELETE FROM room_photos WHERE id = ?", [$id]);

        // If this was the cover, promote the next photo
        if ($photo['is_cover']) {
            Database::query(
                "UPDATE room_photos SET is_cover = 1 WHERE room_id = ? ORDER BY sort_order LIMIT 1",
                [$photo['room_id']]
            );
        }

        Response::success(null, 'Photo supprimée');
    }

    public function updateStatus(Request $req, array $params = []): void
    {
        $id     = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $status = $req->input('status');
        $allowed = ['available','occupied','cleaning','maintenance','blocked'];

        if (!in_array($status, $allowed, true)) Response::error('Statut invalide');
        Guard::requireRoom($id);

        Room::updateStatus($id, $status);
        Response::success(null, 'Statut mis à jour');
    }
}
