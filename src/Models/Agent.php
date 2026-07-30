<?php

namespace Models;

use Core\Database;

class Agent extends BaseModel
{
    protected static string $table = 'agents';

    public static function findByNumero(string $numero): ?array
    {
        return self::first(['numero' => $numero]);
    }

    /** Liste admin : chaque agent avec son nombre d'établissements rattachés. */
    public static function allWithStats(): array
    {
        return Database::query(
            "SELECT a.*, COUNT(ae.id) as establishment_count
             FROM agents a
             LEFT JOIN agent_establishments ae ON ae.agent_id = a.id
             GROUP BY a.id
             ORDER BY a.created_at DESC"
        )->fetchAll();
    }

    public static function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public static function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT);
    }

    public static function createAgent(array $data): int
    {
        $data['password_hash'] = self::hashPassword($data['password']);
        unset($data['password']);
        return self::create($data);
    }

    public static function safe(array $agent): array
    {
        unset($agent['password_hash']);
        return $agent;
    }
}
