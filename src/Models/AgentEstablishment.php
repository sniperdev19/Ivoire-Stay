<?php

namespace Models;

use Core\Database;

class AgentEstablishment extends BaseModel
{
    protected static string $table = 'agent_establishments';

    public static function findByEstablishment(int $estabId): ?array
    {
        return self::first(['establishment_id' => $estabId]);
    }

    public static function forAgent(int $agentId): array
    {
        return Database::query(
            "SELECT ae.*, e.name as establishment_name, e.plan
             FROM agent_establishments ae
             JOIN establishments e ON e.id = ae.establishment_id
             WHERE ae.agent_id = ?
             ORDER BY ae.linked_at DESC",
            [$agentId]
        )->fetchAll();
    }
}
