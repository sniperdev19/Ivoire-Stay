<?php

namespace Services;

use Core\Database;

class AuthService
{
    public static function encode(array $payload): string
    {
        $header = self::base64url(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload['iat'] = time();
        $payload['exp'] = time() + JWT_EXPIRY;
        $payload['jti'] = bin2hex(random_bytes(16));
        $body = self::base64url(json_encode($payload));
        $sig  = self::base64url(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));
        return "$header.$body.$sig";
    }

    public static function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        [$header, $body, $sig] = $parts;

        $expected = self::base64url(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));
        if (!hash_equals($expected, $sig)) return null;

        $payload = json_decode(self::base64urlDecode($body), true);
        if (!$payload || $payload['exp'] < time()) return null;
        if (isset($payload['jti']) && self::isRevoked($payload['jti'])) return null;

        return $payload;
    }

    /** Ajoute le jeton à la liste noire jusqu'à sa date d'expiration naturelle. */
    public static function revoke(string $jti, int $exp): void
    {
        Database::query(
            'INSERT INTO token_blacklist (jti, expires_at) VALUES (?, FROM_UNIXTIME(?))
             ON DUPLICATE KEY UPDATE expires_at = VALUES(expires_at)',
            [$jti, $exp]
        );

        // Nettoyage occasionnel des entrées expirées (pas de cron dédié).
        if (random_int(1, 50) === 1) {
            Database::query('DELETE FROM token_blacklist WHERE expires_at < NOW()');
        }
    }

    private static function isRevoked(string $jti): bool
    {
        if ($jti === '') return false;
        $row = Database::query(
            'SELECT 1 FROM token_blacklist WHERE jti = ? AND expires_at > NOW()',
            [$jti]
        )->fetch();
        return (bool) $row;
    }

    private static function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64urlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
