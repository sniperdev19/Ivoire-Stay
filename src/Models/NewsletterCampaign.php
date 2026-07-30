<?php

namespace Models;

use Core\Database;

class NewsletterCampaign extends BaseModel
{
    protected static string $table = 'newsletter_campaigns';

    public static function allRecent(): array
    {
        return Database::query(
            "SELECT * FROM newsletter_campaigns ORDER BY sent_at DESC"
        )->fetchAll();
    }
}
