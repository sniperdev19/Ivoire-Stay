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

        // Un seul appelant de encode() côté login/register : chaque jeton émis
        // correspond bien à une connexion réelle, d'où l'enregistrement ici
        // plutôt que dupliqué dans chaque contrôleur (cf. onglet Compte →
        // "Appareils connectés").
        if (isset($payload['id'])) {
            self::recordSession((int) $payload['id'], $payload['jti'], (int) $payload['exp']);
        }

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
        Database::query(
            'UPDATE user_sessions SET revoked_at = NOW() WHERE jti = ? AND revoked_at IS NULL',
            [$jti]
        );

        // Nettoyage occasionnel des entrées expirées (pas de cron dédié).
        if (random_int(1, 50) === 1) {
            Database::query('DELETE FROM token_blacklist WHERE expires_at < NOW()');
            // Historique de connexion : conservé au-delà de l'expiration du jeton
            // (utile pour repérer une activité suspecte), purgé après 90 jours.
            Database::query('DELETE FROM user_sessions WHERE created_at < NOW() - INTERVAL 90 DAY');
        }
    }

    /** Enregistre une connexion pour l'onglet Compte → "Appareils connectés". */
    private static function recordSession(int $userId, string $jti, int $exp): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

        Database::query(
            'INSERT INTO user_sessions (user_id, jti, ip_address, device_label, user_agent, expires_at)
             VALUES (?, ?, ?, ?, ?, FROM_UNIXTIME(?))',
            [$userId, $jti, $ip, self::deviceLabel($ua), $ua, $exp]
        );
    }

    /** Étiquette lisible ("Chrome sur Windows") à partir du User-Agent — heuristique simple, sans lib externe. */
    private static function deviceLabel(string $ua): string
    {
        if ($ua === '') return 'Appareil inconnu';

        $browser = match (true) {
            str_contains($ua, 'Edg/')                                          => 'Edge',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera')             => 'Opera',
            str_contains($ua, 'CriOS/')                                        => 'Chrome',
            str_contains($ua, 'Chrome/') && !str_contains($ua, 'Chromium')      => 'Chrome',
            str_contains($ua, 'FxiOS/') || str_contains($ua, 'Firefox/')        => 'Firefox',
            str_contains($ua, 'Safari/') && str_contains($ua, 'Version/')       => 'Safari',
            default                                                            => 'Navigateur',
        };

        $os = match (true) {
            str_contains($ua, 'iPhone')  => 'iPhone',
            str_contains($ua, 'iPad')    => 'iPad',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Macintosh') || str_contains($ua, 'Mac OS X') => 'Mac',
            str_contains($ua, 'Linux')   => 'Linux',
            default                      => null,
        };

        return $os ? "$browser sur $os" : $browser;
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
