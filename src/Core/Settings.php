<?php

namespace Core;

/**
 * Réglages plateforme éditables depuis /admin/settings (table platform_settings,
 * clé/valeur — cf. scripts/migration_platform_settings.sql). TOUJOURS un secours
 * sûr : si la table n'existe pas encore (migration pas appliquée sur cet
 * environnement) ou en cas d'erreur DB, retombe silencieusement sur le $default
 * fourni par l'appelant — ne doit jamais faire échouer une requête. Lue depuis
 * config/config.php sur CHAQUE requête (AGENTS_ENABLED), donc le cache statique
 * (une seule requête SQL, toutes les clés d'un coup) est important.
 */
class Settings
{
    private static ?array $cache = null;

    private static function all(): array
    {
        if (self::$cache !== null) return self::$cache;
        try {
            $rows = Database::query('SELECT `key`, `value` FROM platform_settings')->fetchAll();
            self::$cache = array_column($rows, 'value', 'key');
        } catch (\Throwable $e) {
            self::$cache = [];
        }
        return self::$cache;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = self::all()[$key] ?? null;
        return $value !== null ? $value : $default;
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function getFloat(string $key, float $default = 0.0): float
    {
        $value = self::get($key);
        return $value === null ? $default : (float) $value;
    }

    public static function getInt(string $key, int $default = 0): int
    {
        $value = self::get($key);
        return $value === null ? $default : (int) $value;
    }

    public static function set(string $key, string $value): void
    {
        Database::query(
            'INSERT INTO platform_settings (`key`, `value`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
            [$key, $value]
        );
        if (self::$cache !== null) self::$cache[$key] = $value;
    }
}
