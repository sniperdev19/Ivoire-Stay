<?php

namespace Controllers;

use Core\{Request, Response, Database, PlanGate, RateLimiter};
use Models\{Establishment, Room, Booking, PublicClient, ContactMessage, NewsletterSubscriber, Announcement};
use Services\{CalendarService, MailService, NotificationService, PdfService};

class PublicController
{
    /* Boost effectif : le flag doit être actif ET le plan Business toujours valide
       (protège contre un flag resté à 1 après un downgrade de plan) */
    private const BOOST_SQL = "(e.is_boosted = 1 AND e.plan = 'business' AND (e.plan_expires_at IS NULL OR e.plan_expires_at >= NOW()))";

    public function establishments(Request $req, array $params = []): void
    {
        $type = $req->get('type');
        $city = $req->get('city');

        $where  = ['e.is_active = 1', 'e.frozen_at IS NULL'];
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
                ) as cover_photo,
                " . self::BOOST_SQL . " as is_boosted_effective
             FROM establishments e
             LEFT JOIN rooms r ON r.establishment_id = e.id
             LEFT JOIN room_types rt ON rt.establishment_id = e.id
             WHERE " . implode(' AND ', $where) . "
             GROUP BY e.id
             ORDER BY is_boosted_effective DESC, e.name",
            $params
        )->fetchAll();

        // Majoration client (plan Starter uniquement) sur le prix d'appel affiché.
        foreach ($results as &$estab) {
            if ($estab['min_price'] !== null) {
                $estab['min_price'] = PlanGate::applyClientMarkup((float) $estab['min_price'], $estab);
            }
        }
        unset($estab);

        Response::success($results);
    }

    public function search(Request $req, array $params = []): void
    {
        $city     = $req->get('city', '');
        $checkIn  = $req->get('check_in');
        $checkOut = $req->get('check_out');
        $guests   = (int) $req->get('guests', 1);
        $type     = $req->get('type');

        $where  = ['e.is_active = 1', 'e.frozen_at IS NULL'];
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
                ) as cover_photo,
                " . self::BOOST_SQL . " as is_boosted_effective
             FROM establishments e
             LEFT JOIN rooms r ON r.establishment_id = e.id
             LEFT JOIN room_types rt ON rt.establishment_id = e.id
             WHERE " . implode(' AND ', $where) . "
               AND (rt.capacity IS NULL OR rt.capacity >= ?)
             GROUP BY e.id
             ORDER BY is_boosted_effective DESC, min_price ASC",
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

        // Majoration client (plan Starter uniquement) sur le prix d'appel affiché.
        foreach ($results as &$estab) {
            if ($estab['min_price'] !== null) {
                $estab['min_price'] = PlanGate::applyClientMarkup((float) $estab['min_price'], $estab);
            }
        }
        unset($estab);

        Response::success($results);
    }

    public function property(Request $req, array $params = []): void
    {
        $id    = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $estab = Establishment::withStats($id);
        if (!$estab || !$estab['is_active'] || $estab['frozen_at']) Response::notFound('Établissement introuvable');
        $estab['photos'] = Establishment::photos($id);

        $rooms = Room::allWithDetails($id);
        $checkIn  = $req->get('check_in');
        $checkOut = $req->get('check_out');

        // Majoration client (plan Starter uniquement) sur les tarifs affichés — même
        // prix du début à la fin, voir PlanPricingService::applyClientMarkup().
        foreach ($rooms as &$room) {
            foreach (['base_price', 'weekend_price', 'passage_price'] as $priceField) {
                if ($room[$priceField] !== null) {
                    $room[$priceField] = PlanGate::applyClientMarkup((float) $room[$priceField], $estab);
                }
            }
        }
        unset($room);

        // is_available reflète le statut de la chambre (mis à jour depuis le SaaS) ;
        // si des dates sont fournies, on affine avec la disponibilité réelle sur ces
        // dates (Room::available() vérifie déjà statut + chevauchement réservations).
        // Sans dates, seul maintenance/bloquée (mise hors-vente indéfinie) rend la
        // chambre indisponible — "occupée"/"ménage" sont des statuts du jour qui ne
        // doivent pas décourager un client cherchant à réserver un autre jour.
        $availableIds = ($checkIn && $checkOut) ? array_column(Room::available($id, $checkIn, $checkOut), 'id') : null;
        foreach ($rooms as &$room) {
            $room['is_available'] = $availableIds !== null
                ? in_array($room['id'], $availableIds)
                : !in_array($room['status'], ['maintenance', 'blocked'], true);
        }
        unset($room);

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
        $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'public-booking:' . $ip;
        if (RateLimiter::tooManyAttempts($key, 10, 3600)) {
            Response::error('Trop de demandes. Réessayez plus tard.', 429);
        }
        RateLimiter::hit($key, 3600);

        $data        = $req->all();
        $bookingType = in_array($data['booking_type'] ?? 'nuit', ['nuit','weekend','passage'], true)
            ? $data['booking_type'] ?? 'nuit' : 'nuit';
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

        // Pas de réservation dans le passé
        if ($checkIn < date('Y-m-d')) {
            Response::error("La date d'arrivée ne peut pas être dans le passé");
        }

        // La chambre doit exister et appartenir à un établissement actif
        $room = Database::query(
            "SELECT r.*, rt.capacity, e.name as establishment_name,
                    e.plan as establishment_plan, e.plan_expires_at as establishment_plan_expires_at
             FROM rooms r
             JOIN room_types rt ON rt.id = r.room_type_id
             JOIN establishments e ON e.id = r.establishment_id
             WHERE r.id = ? AND e.is_active = 1 AND e.frozen_at IS NULL",
            [$roomId]
        )->fetch();
        if (!$room) Response::notFound('Chambre introuvable');

        // Rejet serveur si la chambre a été mise hors-vente indéfiniment (maintenance/
        // bloquée) depuis le SaaS — filet de sécurité si le client a contourné les
        // vérifications côté page (onglet resté ouvert, appel direct à l'API).
        // "occupée"/"ménage" sont des statuts du jour, sans date de fin : ils ne
        // bloquent pas une réservation sur un autre jour, seul le conflit de dates
        // réel compte (vérifié juste après via Booking::isRoomAvailable).
        if (in_array($room['status'], ['maintenance', 'blocked'], true)) {
            Response::error("Cette chambre n'est plus disponible.", 409);
        }

        // Validation de la capacité
        $guestsCount = (int)($data['guests_count'] ?? 1);
        if ($room['capacity'] && $guestsCount > (int)$room['capacity']) {
            Response::error("Ce type de chambre ne peut accueillir que {$room['capacity']} personne(s) maximum");
        }

        if ($bookingType === 'passage') {
            if ($hours < 1) Response::error('Nombre d\'heures invalide');
        } elseif ($checkOut <= $checkIn) {
            Response::error('La date de départ doit être après l\'arrivée');
        }

        // Vérifié pour tous les types, y compris "passage" — sans heure précise stockée,
        // deux passages le même jour sont indétectables autrement (cf. Booking::isRoomAvailable).
        if (!Booking::isRoomAvailable($roomId, $checkIn, $checkOut)) {
            Response::error('Chambre non disponible pour ces dates', 409);
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
        // Majoration client (plan Starter uniquement) — même prix affiché/facturé du début
        // à la fin (recherche, fiche chambre, réservation, facture), quel que soit le mode
        // de paiement finalement choisi. Voir PlanPricingService::applyClientMarkup().
        $amount = PlanGate::applyClientMarkup($amount, [
            'plan'            => $room['establishment_plan'],
            'plan_expires_at' => $room['establishment_plan_expires_at'],
        ]);

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

        // Notifie l'équipe de l'établissement (in-app + push) — les réservations en ligne
        // n'ont pas de source interne comme les réservations manuelles, ce déclenchement
        // était manquant jusqu'ici.
        $clientName = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
        NotificationService::bookingNew((int) $room['establishment_id'], $clientName ?: 'Client', $room['number'] ?? (string) $roomId, $id);

        // Email de confirmation immédiat — uniquement pour le paiement sur place.
        // Pour le paiement en ligne, la réservation n'est pas encore payée à ce stade :
        // c'est MailService::bookingPaid() (déclenché après paiement confirmé) qui informera le client.
        $payMode = ($data['pay_mode'] ?? 'onsite') === 'online' ? 'online' : 'onsite';
        if ($payMode !== 'online') {
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
        }

        $created = Booking::find($id);

        Response::success([
            'booking_id'     => $id,
            'total_amount'   => $amount,
            'status'         => 'pending',
            'guest_token'    => $created['guest_token'] ?? null,
            'message'        => 'Votre demande a été envoyée. Vous serez contacté pour confirmation.',
        ], 'Demande de réservation reçue', 201);
    }

    public function room(Request $req, array $params = []): void
    {
        $id   = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $room = Database::query(
            "SELECT r.*, rt.name as type_name, rt.base_price, rt.weekend_price, rt.passage_price, rt.capacity,
                    rt.description as type_description, rt.bed_type, rt.beds_count, rt.amenities as type_amenities,
                    e.name as establishment_name, e.type as establishment_type, e.city, e.slug as establishment_slug,
                    e.plan as establishment_plan, e.plan_expires_at, e.online_payment_enabled,
                    (SELECT file_path FROM room_photos WHERE room_id = r.id AND is_cover = 1 LIMIT 1) as cover_photo,
                    (SELECT SUBSTRING_INDEX(GROUP_CONCAT(file_path ORDER BY is_cover DESC, sort_order SEPARATOR '|'), '|', 3)
                     FROM room_photos WHERE room_id = r.id
                    ) as photos_list
             FROM rooms r
             JOIN room_types rt ON rt.id = r.room_type_id
             JOIN establishments e ON e.id = r.establishment_id
             WHERE r.id = ? AND e.is_active = 1 AND e.frozen_at IS NULL",
            [$id]
        )->fetch();

        if (!$room) Response::notFound('Chambre introuvable');

        // Seule une chambre mise hors-vente indéfiniment (maintenance/bloquée) depuis
        // le SaaS reste totalement injoignable sur la vitrine — même règle que
        // Room::available() (recherche). "occupée"/"ménage" sont des statuts du jour,
        // sans date de fin connue : la chambre reste consultable/réservable pour un
        // autre jour, seul le conflit de dates réel bloque (Booking::isRoomAvailable).
        if (in_array($room['status'], ['maintenance', 'blocked'], true)) {
            Response::error("Cette chambre n'est pas disponible à la réservation actuellement.", 409);
        }

        $room['type_amenities'] = !empty($room['type_amenities']) ? json_decode($room['type_amenities'], true) : [];

        $estabForGate = ['plan' => $room['establishment_plan'], 'plan_expires_at' => $room['plan_expires_at']];

        // Majoration client (plan Starter uniquement) sur les tarifs affichés — même
        // prix du début à la fin, voir PlanPricingService::applyClientMarkup().
        foreach (['base_price', 'weekend_price', 'passage_price'] as $priceField) {
            if ($room[$priceField] !== null) {
                $room[$priceField] = PlanGate::applyClientMarkup((float) $room[$priceField], $estabForGate);
            }
        }

        // Le paiement en ligne ('online_payment') est disponible sur tous les plans —
        // forcé sur Starter, optionnel sur Pro/Business via online_payment_enabled.
        // ONLINE_PAYMENTS_ENABLED (verrou v1, config.php) passe outre tout le reste.
        $room['online_payment_enabled'] = ONLINE_PAYMENTS_ENABLED
            && PlanGate::can($estabForGate, 'online_payment')
            && (bool) $room['online_payment_enabled'];

        Response::success($room);
    }

    public function destinations(Request $req, array $params = []): void
    {
        $results = Database::query(
            "SELECT TRIM(SUBSTRING_INDEX(city, ',', -1)) AS city, COUNT(*) as count
             FROM establishments WHERE is_active = 1 AND frozen_at IS NULL
             GROUP BY TRIM(SUBSTRING_INDEX(city, ',', -1))
             ORDER BY count DESC"
        )->fetchAll();
        Response::success($results);
    }

    // ─── Consultation/téléchargement d'une réservation par le voyageur (sans compte) ──

    /**
     * Preuve de propriété d'une réservation : connaître le guest_token (jeton
     * opaque renvoyé une seule fois à la création, cf. Models\Booking::create).
     * Même mécanique que PushController::subscribeGuest().
     */
    private function verifyGuestBooking(int $id, string $token): array
    {
        $booking = $id ? Booking::findWithDetails($id) : null;
        if (!$booking || empty($booking['guest_token']) || !hash_equals($booking['guest_token'], $token)) {
            Response::error('Réservation ou jeton invalide', 403);
        }
        return $booking;
    }

    public function bookingShow(Request $req, array $params = []): void
    {
        $id    = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $token = (string) $req->get('token', '');
        if (!$token) Response::error('Jeton requis', 403);

        $booking = $this->verifyGuestBooking($id, $token);
        unset($booking['guest_token']);
        Response::success($booking);
    }

    public function bookingPdf(Request $req, array $params = []): void
    {
        $id    = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        $token = (string) $req->get('token', '');
        if (!$token) Response::error('Jeton requis', 403);

        $booking = $this->verifyGuestBooking($id, $token);

        $pdfPath = PdfService::generateBookingConfirmation($booking);
        $absPath = BASE_PATH . '/' . $pdfPath;
        if (file_exists($absPath) && str_ends_with($absPath, '.pdf')) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="reservation-' . $id . '.pdf"');
            readfile($absPath);
            exit;
        }
        Response::error('Impossible de générer le document', 500);
    }

    /**
     * "Retrouver ma réservation" : le voyageur prouve son identité en saisissant
     * l'email ET/OU le téléphone déjà fournis à la réservation (pas de mot de
     * passe, pas de code envoyé par email — c'est justement le canal qu'il ne
     * consulte pas). Le guest_token de chaque réservation trouvée est renvoyé
     * pour permettre ensuite le téléchargement PDF via bookingPdf(). Un seul
     * des deux champs suffit — accepter la recherche par téléphone seul (ou
     * email seul) affaiblit un peu la vérification d'identité par rapport à
     * exiger les deux, compensé par le rate-limit IP ci-dessous.
     * Une recherche sans résultat n'est pas une erreur (200, tableau vide) :
     * c'est un résultat de recherche normal, pas un problème serveur.
     */
    public function bookingFind(Request $req, array $params = []): void
    {
        $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'public-booking-find:' . $ip;
        if (RateLimiter::tooManyAttempts($key, 10, 3600)) {
            Response::error('Trop de tentatives. Réessayez plus tard.', 429);
        }
        RateLimiter::hit($key, 3600);

        $data  = $req->all();
        $email = trim((string) ($data['email'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));

        if (!$email && !$phone) {
            Response::error('Indiquez un email ou un numéro de téléphone.');
        }
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Email invalide');
        }

        $clients = PublicClient::findAllByEmailOrPhone($email ?: null, $phone ?: null);

        $bookings = [];
        foreach ($clients as $client) {
            $bookings = array_merge($bookings, Booking::publicHistoryForClient((int) $client['id']));
        }
        usort($bookings, fn($a, $b) => strtotime((string) $b['created_at']) <=> strtotime((string) $a['created_at']));

        Response::success($bookings);
    }

    public function sendContact(Request $req, array $params = []): void
    {
        $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'public-contact:' . $ip;
        if (RateLimiter::tooManyAttempts($key, 5, 3600)) {
            Response::error('Trop de messages envoyés. Réessayez plus tard.', 429);
        }
        RateLimiter::hit($key, 3600);

        $data = $req->all();
        $required = ['name', 'email', 'message'];
        foreach ($required as $f) {
            if (empty(trim((string)($data[$f] ?? '')))) Response::error("Champ requis : $f");
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            Response::error('Email invalide');
        }

        // Stocké AVANT toute tentative d'email : source de vérité consultable dans
        // l'admin (/admin/contact-messages), l'email n'est qu'un canal best-effort
        // en plus (un échec SMTP ne doit jamais faire perdre le message du visiteur).
        ContactMessage::create([
            'name'    => $data['name'],
            'email'   => $data['email'],
            'phone'   => $data['phone'] ?? null,
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'],
        ]);

        try {
            MailService::sendContact([
                'name'    => $data['name'],
                'email'   => $data['email'],
                'phone'   => $data['phone'] ?? '',
                'subject' => $data['subject'] ?? 'Contact vitrine',
                'message' => $data['message'],
            ]);
        } catch (\Exception $e) {
            error_log('[Contact] ' . $e->getMessage());
        }

        Response::success([], 'Message envoyé avec succès');
    }

    /** GET /api/public/announcements — bandeau d'annonce affiché sur la vitrine (vitrine/layout.php). */
    public function announcements(Request $req, array $params = []): void
    {
        Response::success(Announcement::activeOne());
    }

    /** POST /api/public/newsletter/subscribe — formulaire footer vitrine. */
    public function newsletterSubscribe(Request $req, array $params = []): void
    {
        $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'newsletter-subscribe:' . $ip;
        if (RateLimiter::tooManyAttempts($key, 5, 3600)) {
            Response::error('Trop de tentatives. Réessayez plus tard.', 429);
        }
        RateLimiter::hit($key, 3600);

        $email = trim((string) $req->input('email', ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Email invalide');
        }

        $existing = NewsletterSubscriber::findByEmail($email);
        if ($existing) {
            if ($existing['unsubscribed_at']) {
                // Réabonnement après désabonnement : même ligne, jeton conservé.
                NewsletterSubscriber::update((int) $existing['id'], ['unsubscribed_at' => null]);
            }
            Response::success([], 'Vous êtes déjà abonné(e) à la newsletter');
        }

        NewsletterSubscriber::create([
            'email'             => $email,
            'unsubscribe_token' => bin2hex(random_bytes(32)),
        ]);

        Response::success([], 'Merci de votre inscription à la newsletter !');
    }

    /** GET /api/public/newsletter/unsubscribe?token=... — lien présent dans chaque campagne. */
    public function newsletterUnsubscribe(Request $req, array $params = []): void
    {
        $token = (string) $req->get('token', '');
        $sub   = $token ? NewsletterSubscriber::findByToken($token) : null;
        if (!$sub) Response::notFound('Lien de désabonnement invalide');

        NewsletterSubscriber::update((int) $sub['id'], ['unsubscribed_at' => date('Y-m-d H:i:s')]);
        Response::success([], 'Vous avez été désabonné(e) de la newsletter');
    }
}