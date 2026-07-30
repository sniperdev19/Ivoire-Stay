<?php

// Remplit la colonne slug des chambres existantes (créées avant l'ajout du
// champ). Idempotent : ne touche que les lignes où slug IS NULL.
// Usage : php scripts/backfill_room_slugs.php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/config.php';

use Core\Database;
use Models\Room;

$rows = Database::query(
    "SELECT r.id, r.number, rt.name as type_name
     FROM rooms r JOIN room_types rt ON rt.id = r.room_type_id
     WHERE r.slug IS NULL"
)->fetchAll();

foreach ($rows as $row) {
    $slug = Room::generateSlug($row['type_name'], $row['number'], (int) $row['id']);
    Room::update((int) $row['id'], ['slug' => $slug]);
    echo "#{$row['id']} → {$slug}\n";
}

echo count($rows) . " chambre(s) mise(s) à jour.\n";
