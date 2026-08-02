<?php

namespace Models;

use Core\Database;

class AgentReferral extends BaseModel
{
    protected static string $table = 'agent_referrals';

    public static function findByEstablishment(int $estabId): ?array
    {
        return self::first(['establishment_id' => $estabId]);
    }

    /** Compte les premiers-abonnements d'un plan pas encore inclus dans un versement. */
    public static function countPending(int $agentId, string $plan): int
    {
        return (int) Database::query(
            "SELECT COUNT(*) FROM agent_referrals WHERE agent_id = ? AND plan = ? AND payout_id IS NULL",
            [$agentId, $plan]
        )->fetchColumn();
    }

    /** Total tous plans confondus — utilisé par la prime "premier arrivé" (CommissionService). */
    public static function countTotal(int $agentId): int
    {
        return (int) Database::query(
            "SELECT COUNT(*) FROM agent_referrals WHERE agent_id = ?",
            [$agentId]
        )->fetchColumn();
    }

    /**
     * Classement "nombre de premiers-abonnements" sur une période [from, to[ —
     * utilisé pour le rang affiché au dashboard agent (mois en cours) et pour
     * la prime "top du mois" (SchedulerService::runAgentMonthlyBonus, mois précédent).
     */
    public static function rankingBetween(string $from, string $to): array
    {
        return Database::query(
            "SELECT agent_id, COUNT(*) as cnt FROM agent_referrals
             WHERE created_at >= ? AND created_at < ?
             GROUP BY agent_id ORDER BY cnt DESC",
            [$from, $to]
        )->fetchAll();
    }

    /** Les N plus anciens premiers-abonnements d'un plan pas encore versés (pour former un lot de 10). */
    public static function oldestPendingIds(int $agentId, string $plan, int $limit): array
    {
        $rows = Database::query(
            "SELECT id FROM agent_referrals
             WHERE agent_id = ? AND plan = ? AND payout_id IS NULL
             ORDER BY created_at ASC LIMIT " . (int) $limit,
            [$agentId, $plan]
        )->fetchAll();
        return array_column($rows, 'id');
    }

    public static function assignPayout(array $ids, int $payoutId): void
    {
        if (!$ids) return;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        Database::query(
            "UPDATE agent_referrals SET payout_id = ? WHERE id IN ($placeholders)",
            array_merge([$payoutId], $ids)
        );
    }

    /** Classement nominatif (top N) sur une période — utilisé par la vue "Classement" du dashboard agent. */
    public static function rankingWithNames(string $from, string $to, int $limit): array
    {
        return Database::query(
            "SELECT ar.agent_id, a.nom, COUNT(*) as cnt
             FROM agent_referrals ar
             JOIN agents a ON a.id = ar.agent_id
             WHERE ar.created_at >= ? AND ar.created_at < ?
             GROUP BY ar.agent_id, a.nom
             ORDER BY cnt DESC
             LIMIT " . (int) $limit,
            [$from, $to]
        )->fetchAll();
    }

    public static function forAgent(int $agentId): array
    {
        return Database::query(
            "SELECT ar.*, e.name as establishment_name
             FROM agent_referrals ar
             JOIN establishments e ON e.id = ar.establishment_id
             WHERE ar.agent_id = ?
             ORDER BY ar.created_at DESC",
            [$agentId]
        )->fetchAll();
    }
}
