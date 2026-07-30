<?php

namespace Services;

use Core\{Database, PlanGate};

/**
 * Recalcule, pour un owner donné, quels établissements dépassent la limite
 * de son plan effectif (le plus élevé parmi ses établissements, même logique
 * que PlanGate::canAddEstablishment) et applique/lève le gel en conséquence.
 *
 * Les plus ANCIENS établissements restent actifs ; les plus récents sont
 * gelés en premier — choix produit délibéré (grandfathering).
 *
 * Point d'entrée unique, appelé à chaque changement de plan effectif :
 * downgrade/cancel/upgrade explicites (SubscriptionController) ainsi que
 * l'expiration passive d'un abonnement (SchedulerService, job quotidien).
 */
class EstablishmentFreezeService
{
    public static function recompute(int $ownerId): void
    {
        $estabs = Database::query(
            "SELECT * FROM establishments WHERE owner_id = ? ORDER BY created_at ASC, id ASC",
            [$ownerId]
        )->fetchAll();
        if (!$estabs) return;

        $allowed = 1;
        foreach ($estabs as $e) {
            $max = PlanGate::maxEstablishments($e);
            if ($max === -1) { $allowed = PHP_INT_MAX; break; }
            $allowed = max($allowed, $max);
        }

        foreach ($estabs as $i => $e) {
            $shouldBeActive  = $i < $allowed;
            $currentlyFrozen = !empty($e['frozen_at']);

            if ($shouldBeActive && $currentlyFrozen) {
                self::unfreeze($e);
            } elseif (!$shouldBeActive && !$currentlyFrozen) {
                self::freeze($e);
            }
            // Sinon : état déjà cohérent — ne pas reculer le délai de grâce
            // d'un établissement déjà gelé si le owner rétrograde encore.
        }
    }

    private static function freeze(array $estab): void
    {
        $hardAt = self::computeHardFreezeDate((int) $estab['id']);

        Database::query(
            "UPDATE establishments SET frozen_at = NOW(), frozen_hard_at = ? WHERE id = ?",
            [$hardAt, $estab['id']]
        );

        NotificationService::establishmentFrozen(
            (int) $estab['id'],
            $estab['name'] ?? ('#' . $estab['id']),
            $hardAt
        );
    }

    private static function unfreeze(array $estab): void
    {
        Database::query(
            "UPDATE establishments SET frozen_at = NULL, frozen_hard_at = NULL WHERE id = ?",
            [$estab['id']]
        );

        NotificationService::establishmentUnfrozen(
            (int) $estab['id'],
            $estab['name'] ?? ('#' . $estab['id'])
        );
    }

    /**
     * Échéance du gel total : lendemain du check-out de la réservation active
     * la plus tardive (laisse les séjours déjà réservés se terminer
     * normalement). Sans réservation active, le gel total est immédiat.
     */
    private static function computeHardFreezeDate(int $estabId): string
    {
        $maxCheckout = Database::query(
            "SELECT MAX(b.check_out) FROM bookings b
             JOIN rooms r ON r.id = b.room_id
             WHERE r.establishment_id = ? AND b.status != 'cancelled' AND b.check_out >= CURDATE()",
            [$estabId]
        )->fetchColumn();

        if (!$maxCheckout) return date('Y-m-d H:i:s');

        return date('Y-m-d 00:00:00', strtotime($maxCheckout . ' +1 day'));
    }
}
