<?php

namespace Controllers;

use Core\{Request, Response, Guard, PlanGate};
use Models\{Booking, Room, Invoice, Establishment};
use Services\{NotificationService, MailService};

class BookingController
{
    private function estabId(Request $req): int
    {
        return Guard::resolveEstabId($req);
    }

    /**
     * Phase B du gel (voir EstablishmentFreezeService) : le délai de grâce est
     * dépassé, plus aucune gestion de réservation existante n'est possible non
     * plus (la phase A, elle, ne bloquait que la création de nouvelles réservations).
     */
    private function requireNotHardFrozen(int $estabId): void
    {
        $estab = Establishment::find($estabId);
        if (PlanGate::isHardFrozen($estab ?? [])) {
            Response::error("Cet établissement est totalement gelé (délai de grâce dépassé), plus aucune action possible. Mettez à niveau votre abonnement pour le réactiver.", 403);
        }
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

        // Établissement en excédent par rapport à la limite d'établissements du
        // plan effectif (voir EstablishmentFreezeService) — plus d'ajout tant que
        // le owner n'a pas remis son abonnement à niveau.
        $estab = Establishment::find($room['establishment_id']);
        if (PlanGate::isFrozen($estab ?? [])) {
            Response::error("Cet établissement dépasse la limite d'établissements de votre plan actuel, impossible d'ajouter une nouvelle réservation. Mettez à niveau votre abonnement pour le réactiver.", 403);
        }

        if ($bookingType === 'passage') {
            if ($hours < 1) Response::error('Nombre d\'heures invalide');
            $checkOut = $checkIn; // passage = même journée
        } elseif ($checkOut <= $checkIn) {
            Response::error('La date de départ doit être après l\'arrivée');
        }

        // Vérifié pour tous les types, y compris "passage" — sans heure précise stockée,
        // deux passages le même jour sont indétectables autrement (cf. Booking::isRoomAvailable).
        if (!Booking::isRoomAvailable($roomId, $checkIn, $checkOut)) {
            Response::error('Chambre non disponible pour ces dates', 409);
        }

        $amount = Booking::calculateAmount($room['room_type_id'], $checkIn, $checkOut, $bookingType, $hours);

        // Handle client: internal user_id or public_client
        $clientId = null;
        $userId   = null;

        if (!empty($data['public_client_id'])) {
            $clientId = (int) $data['public_client_id'];
        } elseif (!empty($data['client'])) {
            // Réutilise le client existant si son email est déjà connu (même
            // logique que le tunnel de réservation public), plutôt que de
            // créer un doublon à chaque réservation.
            $c = $data['client'];
            $clientId = \Models\PublicClient::findOrCreate([
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
        $invoiceAmount = (float) ($data['total_amount'] ?? $amount);
        $invoiceId = Invoice::createForBooking($id, $invoiceAmount);

        // Encaissement optionnel saisi directement dans le formulaire de création
        if ($invoiceId && !empty($data['payment']) && is_array($data['payment'])) {
            $payment      = $data['payment'];
            $validMethods = ['cash', 'mobile_money', 'card', 'bank_transfer'];
            $method       = $payment['method'] ?? '';

            if (in_array($method, $validMethods, true)) {
                $type          = ($payment['type'] ?? 'full') === 'partial' ? 'partial' : 'full';
                $paymentAmount = $type === 'partial'
                    ? min($invoiceAmount, max(0.01, (float) ($payment['amount'] ?? 0)))
                    : $invoiceAmount;

                Invoice::registerPayment($id, $invoiceId, $paymentAmount, $method, $type, $payment['notes'] ?? null);
                NotificationService::paymentReceived($room['establishment_id'], $paymentAmount, $method, $invoiceId);
            }
        }

        // Update room status
        if (($data['status'] ?? 'confirmed') === 'checked_in') {
            Room::updateStatus($roomId, 'occupied');
        }

        // Notification au propriétaire de l'établissement
        $clientName = '';
        if (!empty($data['client'])) {
            $c = $data['client'];
            $clientName = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
        }
        if (!$clientName) $clientName = $user['name'] ?? 'Client';
        NotificationService::bookingNew($room['establishment_id'], $clientName, $room['number'] ?? '?', $id);

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
        $id      = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $booking = Guard::requireBooking($id);

        $data    = $req->all();
        $allowed = ['check_in','check_out','booking_type','hours','guests_count','total_amount','status','notes','source'];
        $update  = array_intersect_key($data, array_flip($allowed));

        if (isset($update['booking_type']) && !in_array($update['booking_type'], ['nuit','weekend','passage'], true)) {
            unset($update['booking_type']);
        }

        // PUT générique : une annulation peut aussi transiter par ici (le
        // frontend n'appelle pas toujours DELETE), donc le même hook de
        // notification que destroy() est nécessaire — sinon une annulation
        // passée par cette route resterait silencieuse.
        $becomingCancelled = ($update['status'] ?? null) === 'cancelled' && $booking['status'] !== 'cancelled';

        if (isset($update['status']) && in_array($update['status'], ['cancelled', 'checked_in', 'checked_out'], true)) {
            $this->requireNotHardFrozen((int) $booking['establishment_id']);
        }

        if (!empty($update)) Booking::update($id, $update);

        if ($becomingCancelled) Invoice::cancelForBooking($id);

        $details = Booking::findWithDetails($id);

        if ($becomingCancelled) {
            NotificationService::bookingCancelled(
                (int) $booking['establishment_id'],
                $details['client_name'] ?? 'Client',
                $details['room_number'] ?? '?',
                $id
            );
            if (!empty($details['client_email'])) MailService::bookingCancelledGuest($details);
        }

        Response::success($details, 'Réservation mise à jour');
    }

    public function destroy(Request $req, array $params = []): void
    {
        $id      = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $booking = Guard::requireBooking($id);
        $this->requireNotHardFrozen((int) $booking['establishment_id']);
        Booking::update($id, ['status' => 'cancelled']);
        Invoice::cancelForBooking($id);

        $details = Booking::findWithDetails($id);
        NotificationService::bookingCancelled(
            (int) $booking['establishment_id'],
            $details['client_name'] ?? 'Client',
            $details['room_number'] ?? '?',
            $id
        );
        if (!empty($details['client_email'])) MailService::bookingCancelledGuest($details);

        Response::success(null, 'Réservation annulée');
    }

    public function checkIn(Request $req, array $params = []): void
    {
        $id      = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $booking = Guard::requireBooking($id);
        $this->requireNotHardFrozen((int) $booking['establishment_id']);

        // Précondition de statut : protège le double-clic (ou deux appareils
        // agissant sur la même réservation, ex. annulée entre-temps ailleurs).
        if ($booking['status'] !== 'confirmed') {
            Response::error('Cette réservation ne peut plus être check-in (statut actuel : ' . $booking['status'] . ').', 409);
        }

        Booking::update($id, ['status' => 'checked_in']);
        Room::updateStatus($booking['room_id'], 'occupied');
        Response::success(null, 'Check-in effectué');
    }

    public function checkOut(Request $req, array $params = []): void
    {
        $id      = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $booking = Guard::requireBooking($id);
        $this->requireNotHardFrozen((int) $booking['establishment_id']);

        if ($booking['status'] !== 'checked_in') {
            Response::error('Cette réservation ne peut plus être check-out (statut actuel : ' . $booking['status'] . ').', 409);
        }

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
