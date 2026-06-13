<?php

namespace Models;

use Core\Database;
use PDO;

abstract class BaseModel
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    public static function find(int $id): ?array
    {
        $stmt = Database::query(
            'SELECT * FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = ? LIMIT 1',
            [$id]
        );
        return $stmt->fetch() ?: null;
    }

    public static function all(string $orderBy = 'id', string $dir = 'ASC'): array
    {
        return Database::query(
            "SELECT * FROM " . static::$table . " ORDER BY $orderBy $dir"
        )->fetchAll();
    }

    public static function where(array $conditions, string $orderBy = 'id', string $dir = 'ASC'): array
    {
        $clauses = array_map(fn($k) => "$k = ?", array_keys($conditions));
        $sql = "SELECT * FROM " . static::$table
             . " WHERE " . implode(' AND ', $clauses)
             . " ORDER BY $orderBy $dir";
        return Database::query($sql, array_values($conditions))->fetchAll();
    }

    public static function first(array $conditions): ?array
    {
        $clauses = array_map(fn($k) => "$k = ?", array_keys($conditions));
        $sql = "SELECT * FROM " . static::$table
             . " WHERE " . implode(' AND ', $clauses)
             . " LIMIT 1";
        return Database::query($sql, array_values($conditions))->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $cols = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        Database::query(
            "INSERT INTO " . static::$table . " ($cols) VALUES ($placeholders)",
            array_values($data)
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $sets = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
        $values = array_values($data);
        $values[] = $id;
        $stmt = Database::query(
            "UPDATE " . static::$table . " SET $sets WHERE " . static::$primaryKey . " = ?",
            $values
        );
        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::query(
            "DELETE FROM " . static::$table . " WHERE " . static::$primaryKey . " = ?",
            [$id]
        );
        return $stmt->rowCount() > 0;
    }

    public static function count(array $conditions = []): int
    {
        if (empty($conditions)) {
            return (int) Database::query("SELECT COUNT(*) FROM " . static::$table)->fetchColumn();
        }
        $clauses = array_map(fn($k) => "$k = ?", array_keys($conditions));
        $sql = "SELECT COUNT(*) FROM " . static::$table . " WHERE " . implode(' AND ', $clauses);
        return (int) Database::query($sql, array_values($conditions))->fetchColumn();
    }
}
