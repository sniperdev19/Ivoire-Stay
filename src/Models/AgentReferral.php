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
