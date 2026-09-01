<?php

namespace Models;

use Core\Database;

class User extends BaseModel
{
    protected static string $table = 'users';

    public static function findByEmail(string $email): ?array
    {
        return self::first(['email' => $email]);
    }

    public static function findByEstablishment(int $estabId): array
    {
        return self::where(['establishment_id' => $estabId]);
    }

    /** Tous les comptes propriétaires de la plateforme, avec leur nombre d'établissements — vue admin. */
    public static function allOwners(): array
    {
        return Database::query(
            "SELECT u.id, u.name, u.email, u.phone, u.created_at, u.email_verified_at, u.suspended_at,
                    COUNT(e.id) as establishment_count
             FROM users u
             LEFT JOIN establishments e ON e.owner_id = u.id
             WHERE u.role = 'owner'
             GROUP BY u.id
             ORDER BY u.created_at DESC"
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

    public static function createUser(array $data): int
    {
        $data['password_hash'] = self::hashPassword($data['password']);
        unset($data['password']);
        return self::create($data);
    }

    public static function safe(array $user): array
    {
        unset($user['password_hash']);
        return $user;
    }
}
