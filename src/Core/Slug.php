<?php

namespace Core;

/**
 * Génération de tranches d'URL (slugs) lisibles à partir d'un texte libre.
 * Table de translitération explicite plutôt que iconv//TRANSLIT : ce dernier
 * est dépendant de la locale/plateforme et produit des résultats corrompus
 * sur certains environnements Windows (ex: "ôtel" → "o^tel").
 */
class Slug
{
    private const MAP = [
        'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a',
        'ç'=>'c',
        'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
        'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i',
        'ñ'=>'n',
        'ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
        'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u',
        'ý'=>'y','ÿ'=>'y',
        'œ'=>'oe','æ'=>'ae',
    ];

    public static function make(string $text): string
    {
        $text = strtr(mb_strtolower($text, 'UTF-8'), self::MAP);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }

    /** Slug + id en suffixe : unique par construction (l'id l'est déjà). */
    public static function withId(string $text, int $id): string
    {
        $base = self::make($text);
        return ($base !== '' ? $base . '-' : '') . $id;
    }
}
