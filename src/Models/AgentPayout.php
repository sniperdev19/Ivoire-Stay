<?php

namespace Models;

use Core\Database;

class AgentPayout extends BaseModel
{
    protected static string $table = 'agent_payouts';

    public static function findByAgent(int $agentId): array
    {
        return Database::query(
            "SELECT * FROM agent_payouts WHERE agent_id = ? ORDER BY created_at DESC",
            [$agentId]
        )->fetchAll();
    }

    public static function allWithStatus(?string $status = null): array
    {
        $sql = "SELECT ap.*, a.nom as agent_nom, a.numero as agent_numero
                FROM agent_payouts ap
                JOIN agents a ON a.id = ap.agent_id";
        $params = [];
        if ($status) {
            $sql .= " WHERE ap.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY ap.created_at DESC";
        return Database::query($sql, $params)->fetchAll();
    }
}
