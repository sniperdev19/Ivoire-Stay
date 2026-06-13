<?php

namespace Models;

use Core\Database;

class Expense extends BaseModel
{
    protected static string $table = 'expenses';

    public static function findByEstablishment(int $estabId, array $filters = []): array
    {
        $where = ['establishment_id = ?'];
        $params = [$estabId];

        if (!empty($filters['category'])) {
            $where[] = 'category = ?';
            $params[] = $filters['category'];
        }
        if (!empty($filters['from'])) {
            $where[] = 'expense_date >= ?';
            $params[] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[] = 'expense_date <= ?';
            $params[] = $filters['to'];
        }

        return Database::query(
            "SELECT * FROM expenses WHERE " . implode(' AND ', $where) . " ORDER BY expense_date DESC",
            $params
        )->fetchAll();
    }

    public static function totalByMonth(int $estabId, string $month): float
    {
        return (float) Database::query(
            "SELECT COALESCE(SUM(amount), 0) FROM expenses
             WHERE establishment_id = ? AND DATE_FORMAT(expense_date, '%Y-%m') = ?",
            [$estabId, $month]
        )->fetchColumn();
    }

    public static function byCategory(int $estabId, string $month): array
    {
        return Database::query(
            "SELECT category, SUM(amount) as total
             FROM expenses
             WHERE establishment_id = ? AND DATE_FORMAT(expense_date, '%Y-%m') = ?
             GROUP BY category",
            [$estabId, $month]
        )->fetchAll();
    }
}
