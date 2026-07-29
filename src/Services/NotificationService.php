<?php

namespace Services;

use Models\{Notification, Establishment, User};

class NotificationService
{
    public static function create(int $userId, string $type, string $title, string $message = '', array $data = []): void
    {
        // Choke point unique : tout déclencheur (existant ou futur) respecte
        // automatiquement les préférences du destinataire sans avoir à vérifier
        // lui-même — voir NotificationController::updatePreferences().
        if (self::isMuted($userId, $type)) return;

        try {
            Notification::create([
                'user_id' => $userId,
                'type'    => $type,
                'title'   => $title,
                'message' => $message ?: null,
                'data'    => $data ? json_encode($data) : null,
            ]);
        } catch (\Exception $e) {
            error_log('[NotificationService] ' . $e->getMessage());
        }
        // Best-effort : notification navigateur en plus de la ligne en base,
        // pour les cas où le destinataire n'a pas l'app ouverte.
        PushService::sendToUser($userId, $title, $message, $data);
    }

    private static function isMuted(int $userId, string $type): bool
    {
        $user = User::find($userId);
        if (!$user || empty($user['notification_muted_types'])) return false;

        $muted = json_decode($user['notification_muted_types'], true);
        return is_array($muted) && in_array($type, $muted, true);
    }

    /** Notifie tous les superadmins de la plateforme (alertes d'administration globale). */
    public static function forSuperadmins(string $type, string $title, string $message = '', array $data = []): void
    {
        foreach (User::where(['role' => 'superadmin']) as $admin) {
            self::create((int) $admin['id'], $type, $title, $message, $data);
        }
    }

    /**
     * Annonce plateforme envoyée manuellement par un superadmin à tous les
     * propriétaires (AdminController::broadcastNotification()) — pas aux
     * receptionists (comptes équipe, pas destinataires des communications
     * "propriétaire de la plateforme"). Retourne le nombre de destinataires.
     */
    public static function broadcastToOwners(string $title, string $message): int
    {
        $owners = User::where(['role' => 'owner']);
        foreach ($owners as $owner) {
            self::create((int) $owner['id'], 'platform_announcement', $title, $message);
        }
        return count($owners);
    }

    /**
     * Notifie toute l'équipe de l'établissement : le propriétaire et les
     * membres (receptionist) — pas seulement l'owner, puisque ce sont
     * souvent eux qui gèrent les réservations/paiements au quotidien.
     */
    public static function forEstab(int $estabId, string $type, string $title, string $message = '', array $data = []): void
    {
        $estab = Establishment::find($estabId);
        if (!$estab) return;

        $recipients = [];
        if (!empty($estab['owner_id'])) $recipients[] = (int) $estab['owner_id'];
        foreach (User::findByEstablishment($estabId) as $u) {
            if ($u['role'] === 'receptionist') $recipients[] = (int) $u['id'];
        }

        foreach (array_unique($recipients) as $userId) {
            self::create($userId, $type, $title, $message, $data);
        }
    }

    // ── Raccourcis sémantiques ─────────────────────────────────────────────────

    public static function bookingNew(int $estabId, string $clientName, string $roomNumber, int $bookingId): void
    {
        self::forEstab($estabId, 'booking_new',
            'Nouvelle réservation',
            $clientName . ' · Chambre ' . $roomNumber,
            ['booking_id' => $bookingId]
        );
    }

    public static function paymentReceived(int $estabId, float $amount, string $method, int $invoiceId): void
    {
        $methods = ['mobile_money' => 'Mobile Money', 'cash' => 'Espèces', 'card' => 'Carte', 'bank_transfer' => 'Virement'];
        $label   = $methods[$method] ?? $method;
        self::forEstab($estabId, 'payment_received',
            'Paiement reçu',
            number_format($amount, 0, ',', ' ') . ' FCFA · ' . $label,
            ['invoice_id' => $invoiceId]
        );
    }

    public static function invoicePaid(int $estabId, string $invoiceNumber, int $invoiceId): void
    {
        self::forEstab($estabId, 'invoice_paid',
            'Facture entièrement réglée',
            'Facture ' . $invoiceNumber,
            ['invoice_id' => $invoiceId]
        );
    }

    public static function invoiceSent(int $estabId, string $invoiceNumber, string $email, int $invoiceId): void
    {
        self::forEstab($estabId, 'invoice_sent',
            'Facture envoyée par email',
            $invoiceNumber . ' → ' . $email,
            ['invoice_id' => $invoiceId]
        );
    }

    public static function bookingCancelled(int $estabId, string $clientName, string $roomNumber, int $bookingId): void
    {
        self::forEstab($estabId, 'booking_cancelled',
            'Réservation annulée',
            $clientName . ' · Chambre ' . $roomNumber,
            ['booking_id' => $bookingId]
        );
    }

    public static function teamMemberAdded(int $estabId, string $memberName): void
    {
        self::forEstab($estabId, 'team_member_added',
            "Nouveau membre d'équipe",
            $memberName . ' a rejoint votre établissement',
            []
        );
    }

    /** Réservé au propriétaire : les receptionists n'ont pas accès à la facturation/abonnement. */
    public static function subscriptionExpiring(int $ownerId, string $planLabel, int $daysLeft): void
    {
        $dayWord = $daysLeft > 1 ? 'jours' : 'jour';
        self::create($ownerId, 'subscription_expiring',
            'Abonnement bientôt expiré',
            "Plan {$planLabel} · expire dans {$daysLeft} {$dayWord}",
            ['days_left' => $daysLeft]
        );
    }

    public static function arrivalReminder(int $estabId, int $count): void
    {
        self::forEstab($estabId, 'arrival_reminder',
            'Arrivées prévues demain',
            $count . ' réservation(s) avec arrivée demain',
            ['count' => $count]
        );
    }

    public static function departureReminder(int $estabId, int $count): void
    {
        self::forEstab($estabId, 'departure_reminder',
            'Départs prévus demain',
            $count . ' réservation(s) avec départ demain',
            ['count' => $count]
        );
    }

    // ── Raccourcis superadmin (alertes plateforme) ──────────────────────────────

    public static function newEstablishment(string $estabName, string $ownerName, int $estabId): void
    {
        self::forSuperadmins('new_establishment',
            'Nouvel établissement inscrit',
            $estabName . ' · ' . $ownerName,
            ['establishment_id' => $estabId]
        );
    }

    public static function payoutRequested(string $estabName, float $amount, int $payoutId): void
    {
        self::forSuperadmins('payout_requested',
            'Demande de retrait',
            $estabName . ' · ' . number_format($amount, 0, ',', ' ') . ' FCFA',
            ['payout_id' => $payoutId]
        );
    }

    public static function subscriptionActivated(string $estabName, string $planLabel, int $estabId): void
    {
        self::forSuperadmins('subscription_activated',
            'Abonnement activé',
            $estabName . ' · Plan ' . $planLabel,
            ['establishment_id' => $estabId]
        );
    }

    /**
     * Palier de 10 premiers-abonnements atteint pour un agent commercial —
     * versement forfaitaire à traiter manuellement (voir AgentPayoutController).
     * Les agents n'ont pas de compte `users` (pas de boîte de notifications
     * propre) : seuls les superadmins, qui traitent le versement, sont notifiés.
     */
    public static function agentPayoutReady(string $agentNom, string $planLabel, float $amount, int $payoutId): void
    {
        self::forSuperadmins('agent_payout_ready',
            'Versement agent à traiter',
            $agentNom . ' · Plan ' . $planLabel . ' · ' . number_format($amount, 0, ',', ' ') . ' FCFA',
            ['agent_payout_id' => $payoutId]
        );
    }

    /**
     * Établissement passé en excédent par rapport à la limite du plan effectif
     * (voir Services\EstablishmentFreezeService) — plus de nouvelles
     * réservations/chambres, invisible sur la vitrine.
     */
    public static function establishmentFrozen(int $estabId, string $estabName, string $hardAt): void
    {
        self::forEstab($estabId, 'establishment_frozen',
            'Établissement gelé',
            $estabName . " · limite d'établissements du plan dépassée. Gestion des réservations en cours possible jusqu'au " . date('d/m/Y', strtotime($hardAt)) . '.',
            ['establishment_id' => $estabId, 'hard_at' => $hardAt]
        );
    }

    /** Établissement redevenu dans la limite du plan effectif — dégelé automatiquement. */
    public static function establishmentUnfrozen(int $estabId, string $estabName): void
    {
        self::forEstab($estabId, 'establishment_unfrozen',
            'Établissement réactivé',
            $estabName . ' · de nouveau accessible et visible sur la vitrine.',
            ['establishment_id' => $estabId]
        );
    }
}
