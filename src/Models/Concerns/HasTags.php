<?php

namespace App\Models\Concerns;

use App\Support\ContentTags;

/**
 * Tagy na modelu obsahu (Article, Course).
 *
 * Normalizace i výchozí tag visí na modelových událostech, ne na
 * Filament formuláři — pravidlo pak platí i pro seedery, tinker a
 * budoucí AI agenty, kteří záznamy zakládají mimo admin.
 *
 * Model, který trait použije, musí mít sloupec 'tags' ve $fillable
 * a cast 'tags' => 'array'.
 */
trait HasTags
{
    public static function bootHasTags(): void
    {
        static::creating(function (self $model): void {
            // Rozlišujeme "tagy nikdo nenastavil" (null) od "redaktor je
            // vědomě vymazal" ([]). Výchozí tag se doplní jen v prvním
            // případě — jinak by se vymazaný tag při uložení vracel.
            if ($model->tags !== null) {
                return;
            }

            $default = ContentTags::defaultTag();

            $model->tags = $default === null ? [] : [$default];
        });

        static::saving(function (self $model): void {
            if ($model->tags !== null) {
                $model->tags = ContentTags::normalizeMany($model->tags);
            }
        });
    }
}
