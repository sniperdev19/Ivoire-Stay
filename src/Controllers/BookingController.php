<?php

namespace Controllers;

use Core\{Request, Response, Guard};
use Models\{Booking, Room, Invoice};

class BookingController
{
    private function estabId(Request $req): int
    {
        return Guard::resolveEstabId($req);
    }

    public function index(Request $req, array $params = []): void
    {
        $estabId = $this->estabId($req);
        if (!$estabId) Response::error('establishment_id requis');

        $filters = array_filter([
            'status' => $req->get('status'),
            'from'   => $req->get('from'),
            'to'     => $req->get('to'),
        ]);

        Response::success(Booking::allWithDetails($estabId, $filters));
    }

    public function store(Request $req, array $params = []): void
    {
        $data = $req->all();
        $user = $_REQUEST['_user'];

        $required = ['room_id', 'check_in', 'check_out'];
        foreach ($required as $f) {
            if (empty($data[$f])) Response::error("Champ requis : $f");
        }

        $roomId      = (int) $data['room_id'];
        $checkIn     = $data['check_in'];
        $checkOut    = $data['check_out'];
        $bookingType = in_array($data['booking_type'] ?? 'nuit', ['nuit','weekend','passage'], true)
            ? $data['booking_type'] : 'nuit';
        $hours       = max(0, min(23, (int) ($data['hours'] ?? 0)));

        // La chambre doit appartenir à un établissement du périmètre de l'utilisateur
        $room = Guard::requireRoom($roomId);

        if ($bookingType === 'passage') {
            if ($hours < 1) Response::error('Nombre d\'heures invalide');
            $checkOut = $checkIn; // passage = même journée
        } elseif ($checkOut <= $checkIn) {
            Response::error('La date de départ doit être après l\'arrivée');
        }

        if ($bookingType !== 'passage' && !Booking::isRoomAvailable($roomId, $checkIn, $checkOut)) {
            Response::error('Chambre non disponible pour ces dates', 409);
        }

        $amount = Booking::calculateAmount($room['room_type_id'], $checkIn, $checkOut, $bookingType, $hours);

        // Handle client: internal user_id or public_client
        $clientId = null;
        $userId   = null;

        if (!empty($data['public_client_id'])) {
            $clientId = (int) $data['public_client_id'];
        } elseif (!empty($data['client'])) {
            // Create public client on the fly
            $c = $data['client'];
            $clientId = \Models\PublicClient::create([
                'first_name'    => $c['first_name'],
                'last_name'     => $c['last_name'],
                'email'         => $c['email'] ?? null,
                'phone'         => $c['phone'] ?? null,
                'id_doc_type'   => $c['id_doc_type'] ?? null,
                'id_doc_number' => $c['id_doc_number'] ?? null,
            ]);
        } else {
            $userId = $user['id'];
        }

        $id = Booking::create([
            'room_id'          => $roomId,
            'user_id'          => $userId,
            'public_client_id' => $clientId,
            'check_in'         => $checkIn,
            'check_out'        => $checkOut,
            'booking_type'     => $bookingType,
            'hours'            => $bookingType === 'passage' ? $hours : null,
            'guests_count'     => (int) ($data['guests_count'] ?? 1),
            'total_amount'     => $data['total_amount'] ?? $amount,
            'status'           => $data['status'] ?? 'confirmed',
            'source'           => $data['source'] ?? 'manual',
            'notes'            => $data['notes'] ?? null,
        ]);

        // Auto-generate invoice
        Invoice::createForBooking($id, (float) ($data['total_amount'] ?? $amount));

        // Update room status
        if (($data['status'] ?? 'confirmed') === 'checked_in') {
            Room::updateStatus($roomId, 'occupied');
        }

        Response::success(Booking::findWithDetails($id), 'Réservation créée', 201);
    }

    public function show(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        Guard::requireBooking($id);
        Response::success(Booking::findWithDetails($id));
    }

    public function update(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        Guard::requireBooking($id);

        $data    = $req->all();
        $allowed = ['check_in','check_out','booking_type','hours','guests_count','total_amount','status','notes','source'];
        $update  = array_intersect_key($data, array_flip($allowed));

        if (isset($update['booking_type']) && !in_array($update['booking_type'], ['nuit','weekend','passage'], true)) {
            unset($update['booking_type']);
        }

        if (!empty($update)) Booking::update($id, $update);
        Response::success(Booking::findWithDetails($id), 'Réservation mise à jour');
    }

    public function destroy(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        Guard::requireBooking($id);
        Booking::update($id, ['status' => 'cancelled']);
        Response::success(null, 'Réservation annulée');
    }

    public function checkIn(Request $req, array $params = []): void
    {
        $id      = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $booking = Guard::requireBooking($id);

        Booking::update($id, ['status' => 'checked_in']);
        Room::updateStatus($booking['room_id'], 'occupied');
        Response::success(null, 'Check-in effectué');
    }

    public function checkOut(Request $req, array $params = []): void
    {
        $id      = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $booking = Guard::requireBooking($id);

        Booking::update($id, ['status' => 'checked_out']);
        Room::updateStatus($booking['room_id'], 'available');
        Response::success(null, 'Check-out effectué');
    }

    public function planning(Request $req, array $params = []): void
    {
        $estabId = Guard::resolveEstabId($req);
        $from    = $req->get('from') ?? date('Y-m-d');
        $to      = $req->get('to')   ?? date('Y-m-d', strtotime('+13 days'));

        if (!$estabId) Response::error('establishment_id requis');

        $rooms    = Room::allWithDetails($estabId);
        $bookings = Booking::forPlanning($estabId, $from, $to);

        Response::success(['rooms' => $rooms, 'bookings' => $bookings, 'from' => $from, 'to' => $to]);
    }
}
