<?php

namespace Core;

use Models\Establishment;

/**
 * Autorisation multi-tenant.
 *
 * Centralise la vérification que l'utilisateur connecté a le droit d'accéder
 * à un établissement donné et aux ressources qui en dépendent (chambres,
 * réservations, factures, paiements, dépenses, clients).
 *
 * Règles :
 *  - superadmin : accès à tout
 *  - owner      : accès aux établissements dont owner_id = user.id
 *  - autres     : accès au seul user.establishment_id
 */
class Guard
{
    /** @var int[]|null cache des établissements accessibles pour la requête courante */
    private static ?array $estabCache = null;

    public static function user(): array
    {
        return $_REQUEST['_user'] ?? [];
    }

    public static function isSuperadmin(): bool
    {
        return (self::user()['role'] ?? '') === 'superadmin';
    }

    /**
     * IDs des établissements accessibles. Pour un superadmin, retourne un
     * tableau vide qui sert de sentinelle « tous » (voir canAccessEstablishment).
     *
     * @return int[]
     */
    public static function establishmentIds(): array
    {
        if (self::$estabCache !== null) return self::$estabCache;

        $user = self::user();
        $role = $user['role'] ?? '';

        if ($role === 'superadmin') {
            return self::$estabCache = [];
        }
        if ($role === 'owner') {
            return self::$estabCache = array_map(
                fn($e) => (int) $e['id'],
                Establishment::findByOwner((int) ($user['id'] ?? 0))
            );
        }
        return self::$estabCache = !empty($user['establishment_id'])
            ? [(int) $user['establishment_id']]
            : [];
    }

    public static function canAccessEstablishment(int $estabId): bool
    {
        if ($estabId <= 0) return false;
        if (self::isSuperadmin()) return true;
        return in_array($estabId, self::establishmentIds(), true);
    }

    public static function requireEstablishment(int $estabId): void
    {
        if (!self::canAccessEstablishment($estabId)) {
            Response::forbidden('Accès non autorisé à cet établissement');
        }
    }

    /**
     * Résout l'établissement ciblé par une requête et vérifie l'autorisation.
     * Un establishment_id fourni par le client est honoré uniquement s'il est
     * accessible ; sinon on retombe sur l'établissement de l'utilisateur.
     */
    public static function resolveEstabId(Request $req): int
    {
        $user      = self::user();
        $requested = $req->get('establishment_id') ?? $req->input('establishment_id');

        if ($requested !== null && $requested !== '') {
            $id = (int) $requested;
            self::requireEstablishment($id);
            return $id;
        }

        $default = (int) ($user['establishment_id'] ?? 0);
        if (!$default && ($user['role'] ?? '') === 'owner') {
            $owned   = self::establishmentIds();
            $default = $owned[0] ?? 0;
        }
        return $default;
    }

    // ─── Vérifications par ressource ────────────────────────────────────────
    // Chaque méthode renvoie la ligne demandée ou interrompt la requête
    // (404 si introuvable, 403 si hors périmètre).

    public static function requireBooking(int $id): array
    {
        $row = Database::query(
            "SELECT b.*, r.establishment_id
             FROM bookings b JOIN rooms r ON r.id = b.room_id
             WHERE b.id = ?",
            [$id]
        )->fetch();
        if (!$row) Response::notFound('Réservation introuvable');
        self::requireEstablishment((int) $row['establishment_id']);
        return $row;
    }

    public static function requireRoom(int $id): array
    {
        $row = Database::query(
            "SELECT * FROM rooms WHERE id = ?", [$id]
        )->fetch();
        if (!$row) Response::notFound('Chambre introuvable');
        self::requireEstablishment((int) $row['establishment_id']);
        return $row;
    }

    public static function requireRoomType(int $id): array
    {
        $row = Database::query(
            "SELECT * FROM room_types WHERE id = ?", [$id]
        )->fetch();
        if (!$row) Response::notFound('Type de chambre introuvable');
        self::requireEstablishment((int) $row['establishment_id']);
        return $row;
    }

    public static function requireRoomPhoto(int $id): array
    {
        $row = Database::query(
            "SELECT rp.*, r.establishment_id
             FROM room_photos rp JOIN rooms r ON r.id = rp.room_id
             WHERE rp.id = ?",
            [$id]
        )->fetch();
        if (!$row) Response::notFound('Photo introuvable');
        self::requireEstablishment((int) $row['establishment_id']);
        return $row;
    }

    public static function requireEstablishmentPhoto(int $id): array
    {
        $row = Database::query(
            "SELECT * FROM establishment_photos WHERE id = ?", [$id]
        )->fetch();
        if (!$row) Response::notFound('Photo introuvable');
        self::requireEstablishment((int) $row['establishment_id']);
        return $row;
    }

    public static function requireInvoice(int $id): array
    {
        $row = Database::query(
            "SELECT i.*, r.establishment_id
             FROM invoices i
             JOIN bookings b ON b.id = i.booking_id
             JOIN rooms r ON r.id = b.room_id
             WHERE i.id = ?",
            [$id]
        )->fetch();
        if (!$row) Response::notFound('Facture introuvable');
        self::requireEstablishment((int) $row['establishment_id']);
        return $row;
    }

    public static function requirePayment(int $id): array
    {
        $row = Database::query(
            "SELECT p.*, r.establishment_id
             FROM payments p
             JOIN bookings b ON b.id = p.booking_id
             JOIN rooms r ON r.id = b.room_id
             WHERE p.id = ?",
            [$id]
        )->fetch();
        if (!$row) Response::notFound('Paiement introuvable');
        self::requireEstablishment((int) $row['establishment_id']);
        return $row;
    }

    public static function requireExpense(int $id): array
    {
        $row = Database::query(
            "SELECT * FROM expenses WHERE id = ?", [$id]
        )->fetch();
        if (!$row) Response::notFound('Dépense introuvable');
        self::requireEstablishment((int) $row['establishment_id']);
        return $row;
    }

    public static function requireExpenseCategory(int $id): array
    {
        $row = Database::query(
            "SELECT * FROM expense_categories WHERE id = ?", [$id]
        )->fetch();
        if (!$row) Response::notFound('Catégorie introuvable');
        self::requireEstablishment((int) $row['establishment_id']);
        return $row;
    }

    public static function requirePayoutRequest(int $id): array
    {
        $row = Database::query(
            "SELECT * FROM payout_requests WHERE id = ?", [$id]
        )->fetch();
        if (!$row) Response::notFound('Demande de retrait introuvable');
        self::requireEstablishment((int) $row['establishment_id']);
        return $row;
    }

    /**
     * Un membre d'équipe (rôle receptionist) est accessible s'il appartient à un
     * établissement du périmètre de l'appelant. Les comptes owner/superadmin ne
     * sont jamais gérables via ce point d'entrée (création/édition/suppression
     * de membres), même par un autre superadmin.
     */
    public static function requireTeamMember(int $id): array
    {
        $row = Database::query("SELECT * FROM users WHERE id = ?", [$id])->fetch();
        if (!$row) Response::notFound('Membre introuvable');
        if (in_array($row['role'], ['owner', 'superadmin'], true)) {
            Response::forbidden('Action non autorisée sur ce compte');
        }
        self::requireEstablishment((int) ($row['establishment_id'] ?? 0));
        return $row;
    }

    /**
     * Un client public est accessible s'il possède au moins une réservation
     * dans un établissement du périmètre de l'utilisateur.
     */
    public static function requireClient(int $id): array
    {
        $row = Database::query(
            "SELECT * FROM public_clients WHERE id = ?", [$id]
        )->fetch();
        if (!$row) Response::notFound('Client introuvable');

        if (self::isSuperadmin()) return $row;

        $ids = self::establishmentIds();
        if (!$ids) Response::forbidden('Accès non autorisé');

        $in    = implode(',', array_fill(0, count($ids), '?'));
        $linked = (int) Database::query(
            "SELECT COUNT(*) FROM bookings b
             JOIN rooms r ON r.id = b.room_id
             WHERE b.public_client_id = ? AND r.establishment_id IN ($in)",
            array_merge([$id], $ids)
        )->fetchColumn();

        if ($linked === 0) Response::forbidden('Accès non autorisé à ce client');
        return $row;
    }
}
