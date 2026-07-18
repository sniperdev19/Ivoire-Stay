<?php

// Remplit la colonne slug pour les établissements existants (créés avant
// l'introduction du champ). Idempotent : ne touche que les lignes où
// slug IS NULL. Réutilise Establishment::generateSlug() (même logique
// qu'à la création), donc pas de divergence entre backfill et création.
// Usage : php scripts/backfill_establishment_slugs.php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/config.php';

use Core\Database;
use Models\Establishment;

$rows = Database::query("SELECT id, name FROM establishments WHERE slug IS NULL")->fetchAll();

foreach ($rows as $row) {
    $slug = Establishment::generateSlug($row['name'], (int) $row['id']);
    Establishment::update((int) $row['id'], ['slug' => $slug]);
    echo "#{$row['id']} → {$slug}\n";
}

echo count($rows) . " établissement(s) mis à jour.\n";
