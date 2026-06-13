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
