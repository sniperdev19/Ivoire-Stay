<?php

namespace Models;

use Core\Database;
use PDOException;

/**
 * Registre des primes agents décernées (cf. scripts/migration_agent_bonuses.sql
 * pour la sémantique de `scope_key` selon `type`). La contrainte UNIQUE
 * (type, scope_key) est LA garantie anti-double-versement — tryAward() s'appuie
 * dessus plutôt que sur un "SELECT puis INSERT" (non sûr en cas de requêtes
 * concurrentes, ex. deux agents qui franchissent le seuil "premier arrivé"
 * quasi simultanément).
 */
class AgentBonusAward extends BaseModel
{
    protected static string $table = 'agent_bonus_awards';

    /**
     * Tente de décerner la prime ; renvoie l'id créé, ou null si `scope_key`
     * est déjà pris (prime déjà décernée à un autre — ou ce même — agent).
     */
    public static function tryAward(int $agentId, string $type, string $scopeKey, float $amount): ?int
    {
        try {
            return self::create([
                'agent_id'  => $agentId,
                'type'      => $type,
                'scope_key' => $scopeKey,
                'amount'    => $amount,
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') return null; // uniq_type_scope déjà pris
            throw $e;
        }
    }

    public static function existsForType(string $type): bool
    {
        return (bool) Database::query(
            "SELECT 1 FROM agent_bonus_awards WHERE type = ? LIMIT 1",
            [$type]
        )->fetchColumn();
    }

    public static function assignPayout(int $id, int $payoutId): void
    {
        Database::query("UPDATE agent_bonus_awards SET payout_id = ? WHERE id = ?", [$payoutId, $id]);
    }

    public static function forAgent(int $agentId): array
    {
        return Database::query(
            "SELECT * FROM agent_bonus_awards WHERE agent_id = ? ORDER BY awarded_at DESC",
            [$agentId]
        )->fetchAll();
    }

    /** Vue admin : primes récentes avec le nom de l'agent (admin/agents.php). */
    public static function recent(int $limit = 30): array
    {
        return Database::query(
            "SELECT b.*, a.nom as agent_nom
             FROM agent_bonus_awards b
             JOIN agents a ON a.id = b.agent_id
             ORDER BY b.awarded_at DESC LIMIT " . (int) $limit
        )->fetchAll();
    }
}
