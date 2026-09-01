<?php

namespace Models;

use Core\Database;

class AgentProspect extends BaseModel
{
    protected static string $table = 'agent_prospects';

    public const STATUSES = ['a_contacter', 'contacte', 'interesse', 'inscrit', 'perdu'];

    public static function forAgent(int $agentId): array
    {
        return Database::query(
            "SELECT * FROM agent_prospects WHERE agent_id = ? ORDER BY created_at DESC",
            [$agentId]
        )->fetchAll();
    }

    /** Prospect appartenant bien à l'agent connecté — évite qu'un agent modifie/supprime celui d'un autre. */
    public static function findForAgent(int $id, int $agentId): ?array
    {
        return self::first(['id' => $id, 'agent_id' => $agentId]);
    }
}
