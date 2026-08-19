<?php

namespace Models;

class ExpenseCategory extends BaseModel
{
    protected static string $table = 'expense_categories';

    public static function findByEstablishment(int $estabId): array
    {
        return self::where(['establishment_id' => $estabId], 'name');
    }
}
