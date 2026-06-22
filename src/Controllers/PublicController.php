<?php

namespace Controllers;

use Core\{Request, Response, Database};
use Models\{Establishment, Room, Booking, PublicClient};
use Services\{CalendarService, MailService};

class PublicController
{
    public function establishments(Request $req, array $params = []): void
    {
        $type = $req->get('type');
        $city = $req->get('city');

        $where  = ['e.is_active = 1'];
        $params = [];

        if ($type) { $where[] = 'e.type = ?'; $params[] = $type; }
        if ($city) { $where[] = 'e.city LIKE ?'; $params[] = "%$city%"; }

        $results = Database::query(
            "SELECT e.*,
                COUNT(DISTINCT r.id) as total_rooms,
                MIN(rt.base_price) as min_price,
                COALESCE(e.cover_photo,
                    (SELECT file_path FROM room_photos rp
                     JOIN rooms rr ON rr.id = rp.room_id
                     WHERE rr.establishment_id = e.id AND rp.is_cover = 1 LIMIT 1)
                ) as cover_photo
             FROM establishments e
             LEFT JOIN rooms r ON r.establishment_id = e.id
             LEFT JOIN room_types rt ON rt.establishment_id = e.id
             WHERE " . implode(' AND ', $where) . "
             GROUP BY e.id
             ORDER BY e.name",
            $params
        )->fetchAll();

        Response::success($results);
    }

    public function search(Request $req, array $params = []): void
    {
        $city     = $req->get('city', '');
        $checkIn  = $req->get('check_in');
        $checkOut = $req->get('check_out');
        $guests   = (int) $req->get('guests', 1);
        $type     = $req->get('type');

        $where  = ['e.is_active = 1'];
        $qParams = [];

        if ($city) { $where[] = '(e.city LIKE ? OR e.address LIKE ?)'; $qParams[] = "%$city%"; $qParams[] = "%$city%"; }
        if ($type) { $where[] = 'e.type = ?'; $qParams[] = $type; }

        $results = Database::query(
            "SELECT DISTINCT e.*,
                MIN(rt.base_price) as min_price,
                COUNT(DISTINCT r.id) as total_rooms,
                COALESCE(e.cover_photo,
                    (SELECT file_path FROM room_photos rp
                     JOIN rooms rr ON rr.id = rp.room_id
                     WHERE rr.establishment_id = e.id AND rp.is_cover = 1 LIMIT 1)
                ) as cover_photo
             FROM establishments e
             LEFT JOIN rooms r ON r.establishment_id = e.id
             LEFT JOIN room_types rt ON rt.establishment_id = e.id
             WHERE " . implode(' AND ', $where) . "
               AND (rt.capacity IS NULL OR rt.capacity >= ?)
             GROUP BY e.id
             ORDER BY min_price ASC",
            array_merge($qParams, [$guests])
        )->fetchAll();

        // Filter by availability if dates provided
        if ($checkIn && $checkOut) {
            $results = array_filter($results, function ($estab) use ($checkIn, $checkOut, $guests) {
                $available = Room::available((int)$estab['id'], $checkIn, $checkOut);
                return count($available) > 0;
            });
            $results = array_values($results);
        }

        Response::success($results);
    }

    public function property(Request $req, array $params = []): void
    {
        $id    = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $estab = Establishment::withStats($id);
        if (!$estab || !$estab['is_active']) Response::notFound('Établissement introuvable');

        $rooms = Room::allWithDetails($id);
        $checkIn  = $req->get('check_in');
        $checkOut = $req->get('check_out');

        if ($checkIn && $checkOut) {
            $available = array_column(Room::available($id, $checkIn, $checkOut), 'id');
            foreach ($rooms as &$room) {
                $room['is_available'] = in_array($room['id'], $available);
            }
        }

        Response::success(['establishment' => $estab, 'rooms' => $rooms]);
    }

    public function availability(Request $req, array $params = []): void
    {
        $id       = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $from     = $req->get('from') ?? date('Y-m-d');
        $to       = $req->get('to')   ?? date('Y-m-d', strtotime('+30 days'));
        $calendar = CalendarService::getRoomAvailability($id, $from, $to);
        Response::success($calendar);
    }

    public function bookingRequest(Request $req, array $params = []): void
    {
        $data        = $req->all();
        $bookingType = in_array($data['booking_type'] ?? 'nuit', ['nuit','weekend','passage'], true)
            ? $data['booking_type'] : 'nuit';
        $hours       = max(0, min(23, (int) ($data['hours'] ?? 0)));

        $required = ['room_id','check_in','first_name','last_name','email','phone'];
        if ($bookingType !== 'passage') $required[] = 'check_out';
        foreach ($required as $f) {
            if (empty($data[$f])) Response::error("Champ requis : $f");
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            Response::error('Email invalide');
        }

        $roomId   = (int) $data['room_id'];
        $checkIn  = $data['check_in'];
        $checkOut = $bookingType === 'passage' ? $checkIn : $data['check_out'];

        // La chambre doit exister et appartenir à un établissement actif
        $room = Database::query(
            "SELECT r.*, e.name as establishment_name FROM rooms r
             JOIN establishments e ON e.id = r.establishment_id
             WHERE r.id = ? AND e.is_active = 1",
            [$roomId]
        )->fetch();
        if (!$room) Response::notFound('Chambre introuvable');

        if ($bookingType === 'passage') {
            if ($hours < 1) Response::error('Nombre d\'heures invalide');
        } else {
            if ($checkOut <= $checkIn) Response::error('La date de départ doit être après l\'arrivée');
            if (!Booking::isRoomAvailable($roomId, $checkIn, $checkOut)) {
                Response::error('Chambre non disponible pour ces dates', 409);
            }
        }

        $clientId = PublicClient::findOrCreate([
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'email'         => $data['email'],
            'phone'         => $data['phone'],
            'id_doc_type'   => $data['id_doc_type'] ?? null,
            'id_doc_number' => $data['id_doc_number'] ?? null,
        ]);

        $amount = Booking::calculateAmount($room['room_type_id'], $checkIn, $checkOut, $bookingType, $hours);

        $id = Booking::create([
            'room_id'          => $roomId,
            'user_id'          => null,
            'public_client_id' => $clientId,
            'check_in'         => $checkIn,
            'check_out'        => $checkOut,
            'booking_type'     => $bookingType,
            'hours'            => $bookingType === 'passage' ? $hours : null,
            'guests_count'     => (int) ($data['guests_count'] ?? 1),
            'total_amount'     => $amount,
            'status'           => 'pending',
            'source'           => 'online',
            'notes'            => $data['notes'] ?? null,
        ]);

        \Models\Invoice::createForBooking($id, $amount);

        // Email de confirmation (paiement sur place)
        MailService::bookingConfirmation([
            'booking_id'         => $id,
            'first_name'         => $data['first_name'],
            'last_name'          => $data['last_name'],
            'client_email'       => $data['email'],
            'establishment_name' => $room['establishment_name'] ?? '',
            'room_number'        => $room['number'] ?? $roomId,
            'booking_type'       => $bookingType,
            'hours'              => $hours,
            'check_in'           => $checkIn,
            'check_out'          => $checkOut,
            'total_amount'       => $amount,
        ]);

        Response::success([
            'booking_id'     => $id,
            'total_amount'   => $amount,
            'status'         => 'pending',
            'message'        => 'Votre demande a été envoyée. Vous serez contacté pour confirmation.',
        ], 'Demande de réservation reçue', 201);
    }

    public function room(Request $req, array $params = []): void
    {
        $id   = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $room = Database::query(
            "SELECT r.*, rt.name as type_name, rt.base_price, rt.weekend_price, rt.passage_price, rt.capacity,
                    rt.description as type_description,
                    e.name as establishment_name, e.type as establishment_type, e.city,
                    (SELECT file_path FROM room_photos WHERE room_id = r.id AND is_cover = 1 LIMIT 1) as cover_photo
             FROM rooms r
             JOIN room_types rt ON rt.id = r.room_type_id
             JOIN establishments e ON e.id = r.establishment_id
             WHERE r.id = ? AND e.is_active = 1",
            [$id]
        )->fetch();

        if (!$room) Response::notFound('Chambre introuvable');
        Response::success($room);
    }

    public function destinations(Request $req, array $params = []): void
    {
        $results = Database::query(
            "SELECT TRIM(SUBSTRING_INDEX(city, ',', -1)) AS city, COUNT(*) as count
             FROM establishments WHERE is_active = 1
             GROUP BY TRIM(SUBSTRING_INDEX(city, ',', -1))
             ORDER BY count DESC"
        )->fetchAll();
        Response::success($results);
    }
}
