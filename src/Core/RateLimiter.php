<?php

namespace Core;

/**
 * Limiteur de tentatives basé sur la base de données (pas de Redis/APCu
 * disponible dans cet environnement). Une fenêtre glissante simple par clé
 * ("login:<ip>:<email>", "register:<ip>", ...).
 */
class RateLimiter
{
    public static function tooManyAttempts(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $row = Database::query(
            'SELECT attempts, expires_at FROM rate_limits WHERE bucket = ?',
            [$key]
        )->fetch();

        if (!$row || strtotime($row['expires_at']) < time()) {
            return false;
        }

        return (int) $row['attempts'] >= $maxAttempts;
    }

    public static function hit(string $key, int $decaySeconds): void
    {
        $row = Database::query(
            'SELECT expires_at FROM rate_limits WHERE bucket = ?',
            [$key]
        )->fetch();

        if (!$row || strtotime($row['expires_at']) < time()) {
            Database::query(
                'INSERT INTO rate_limits (bucket, attempts, expires_at) VALUES (?, 1, ?)
                 ON DUPLICATE KEY UPDATE attempts = 1, expires_at = VALUES(expires_at)',
                [$key, date('Y-m-d H:i:s', time() + $decaySeconds)]
            );
        } else {
            Database::query('UPDATE rate_limits SET attempts = attempts + 1 WHERE bucket = ?', [$key]);
        }

        // Nettoyage occasionnel des entrées expirées (pas de cron dédié).
        if (random_int(1, 50) === 1) {
            Database::query('DELETE FROM rate_limits WHERE expires_at < NOW()');
        }
    }

    public static function clear(string $key): void
    {
        Database::query('DELETE FROM rate_limits WHERE bucket = ?', [$key]);
    }

    public static function secondsUntilReset(string $key): int
    {
        $row = Database::query('SELECT expires_at FROM rate_limits WHERE bucket = ?', [$key])->fetch();
        return $row ? max(0, strtotime($row['expires_at']) - time()) : 0;
    }
}
