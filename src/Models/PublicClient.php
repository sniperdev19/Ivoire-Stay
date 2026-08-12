<?php

namespace Models;

use Core\Database;

class PublicClient extends BaseModel
{
    protected static string $table = 'public_clients';

    public static function findOrCreate(array $data): int
    {
        $existing = self::first(['email' => $data['email']]);
        if ($existing) {
            // Synchronise les coordonnées avec la dernière saisie : sans cela, un client
            // qui réserve avec un email déjà connu (mais un nom/téléphone différent, ex.
            // email partagé) voyait ses vraies infos silencieusement ignorées au profit
            // de celles du tout premier client enregistré sous cet email.
            self::update($existing['id'], array_filter([
                'first_name'    => $data['first_name']    ?? null,
                'last_name'     => $data['last_name']     ?? null,
                'phone'         => $data['phone']         ?? null,
                'id_doc_type'   => $data['id_doc_type']   ?? null,
                'id_doc_number' => $data['id_doc_number'] ?? null,
            ], fn($v) => $v !== null && $v !== ''));
            return $existing['id'];
        }
        return self::create($data);
    }

    /**
     * Retrouve les voyageurs correspondant à un email et/ou un téléphone (au
     * moins un des deux) — utilisé par PublicController::bookingFind() pour
     * l'accès à l'historique de réservations sans compte ni mot de passe. Le
     * téléphone est normalisé (chiffres uniquement, indicatif 225 retiré, 10
     * derniers chiffres) pour tolérer les variantes de saisie ("+225 01 23...",
     * "01-23-...", etc.), même logique que isValidCiPhone() côté register.js.
     * Peut renvoyer plusieurs voyageurs (téléphone partagé entre proches, par ex.).
     *
     * Recherche par téléphone en PHP (pas de colonne normalisée en base) : un
     * léger balayage de la table, acceptable au volume actuel d'une table de
     * fiches voyageurs plutôt que d'introduire une colonne dédiée pour ce
     * seul usage.
     */
    public static function findAllByEmailOrPhone(?string $email, ?string $phone): array
    {
        $normalize = function (string $v): string {
            $digits = preg_replace('/\D/', '', $v) ?? '';
            if (str_starts_with($digits, '225')) $digits = substr($digits, 3);
            return substr($digits, -10);
        };

        $byId = [];

        if ($email) {
            $row = self::first(['email' => $email]);
            if ($row) $byId[$row['id']] = $row;
        }

        if ($phone) {
            $target = $normalize($phone);
            if ($target !== '') {
                foreach (Database::query("SELECT * FROM public_clients")->fetchAll() as $row) {
                    if ($normalize((string) ($row['phone'] ?? '')) === $target) {
                        $byId[$row['id']] = $row;
                    }
                }
            }
        }

        return array_values($byId);
    }

    /**
     * Liste les clients avec leur nombre de réservations et leur montant
     * réellement dépensé (somme des paiements complétés, pas le montant
     * réservé — un booking non payé/partiellement payé ne doit pas gonfler
     * ce total, cohérent avec `paid_amount` dans Booking::findWithDetails()
     * et le calcul de revenu de ReportController).
     *
     * COUNT(DISTINCT b.id) est nécessaire : la jointure invoices/payments
     * démultiplie les lignes par booking dès qu'il y a plusieurs paiements
     * (paiement partiel + solde) sur la même facture.
     *
     * @param int[]|null $estabIds null = tous (superadmin) ; sinon restreint aux
     *                             clients ayant au moins une réservation dans ces
     *                             établissements.
     */
    public static function allWithBookingCount(?array $estabIds = null, string $search = ''): array
    {
        $search  = trim($search);
        $like    = '%' . $search . '%';
        $searchSql = $search !== ''
            ? ' AND (pc.first_name LIKE ? OR pc.last_name LIKE ? OR pc.email LIKE ? OR pc.phone LIKE ?)'
            : '';
        $searchParams = $search !== '' ? [$like, $like, $like, $like] : [];

        if ($estabIds === null) {
            return Database::query(
                "SELECT pc.*, COUNT(DISTINCT b.id) as booking_count,
                        MAX(b.created_at) as last_booking,
                        COALESCE(SUM(p.amount), 0) as total_spent
                 FROM public_clients pc
                 LEFT JOIN bookings b ON b.public_client_id = pc.id
                 LEFT JOIN invoices i ON i.booking_id = b.id
                 LEFT JOIN payments p ON p.invoice_id = i.id AND p.status = 'completed'
                 WHERE 1=1{$searchSql}
                 GROUP BY pc.id
                 ORDER BY pc.created_at DESC",
                $searchParams
            )->fetchAll();
        }

        if (!$estabIds) return [];

        $in = implode(',', array_fill(0, count($estabIds), '?'));
        return Database::query(
            "SELECT pc.*, COUNT(DISTINCT b.id) as booking_count,
                    MAX(b.created_at) as last_booking,
                    COALESCE(SUM(p.amount), 0) as total_spent
             FROM public_clients pc
             JOIN bookings b ON b.public_client_id = pc.id
             JOIN rooms r ON r.id = b.room_id
             LEFT JOIN invoices i ON i.booking_id = b.id
             LEFT JOIN payments p ON p.invoice_id = i.id AND p.status = 'completed'
             WHERE r.establishment_id IN ($in){$searchSql}
             GROUP BY pc.id
             ORDER BY pc.created_at DESC",
            array_merge($estabIds, $searchParams)
        )->fetchAll();
    }

    /**
     * Client + agrégats (total_bookings/total_spent) restreints aux
     * établissements de $estabIds — utilisé par le modal détail (même
     * définition de "dépensé" que allWithBookingCount()). $estabIds = null
     * pour un superadmin (aucune restriction).
     */
    public static function withStats(int $id, ?array $estabIds = null): ?array
    {
        $where  = ['pc.id = ?'];
        $params = [$id];

        if ($estabIds !== null) {
            if (!$estabIds) return null;
            $in = implode(',', array_fill(0, count($estabIds), '?'));
            $where[] = "(b.id IS NULL OR r.establishment_id IN ($in))";
            $params  = array_merge($params, $estabIds);
        }

        $row = Database::query(
            "SELECT pc.*, COUNT(DISTINCT b.id) as total_bookings,
                    MAX(b.created_at) as last_visit,
                    COALESCE(SUM(p.amount), 0) as total_spent
             FROM public_clients pc
             LEFT JOIN bookings b ON b.public_client_id = pc.id
             LEFT JOIN rooms r ON r.id = b.room_id
             LEFT JOIN invoices i ON i.booking_id = b.id
             LEFT JOIN payments p ON p.invoice_id = i.id AND p.status = 'completed'
             WHERE " . implode(' AND ', $where) . "
             GROUP BY pc.id",
            $params
        )->fetch();

        return $row ?: null;
    }
}
