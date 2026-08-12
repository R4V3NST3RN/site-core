<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Course;
use Illuminate\Support\Str;

/**
 * Tagy obsahu — normalizace, našeptávání a výchozí tag.
 *
 * Tagy jsou ploché pole stringů na modelu, ne relace. Jádro tu drží jen
 * PRAVIDLA; konkrétní hodnota výchozího tagu je brand-specifická a čte
 * se z config('site.content.default_tag'), který je per-web.
 *
 * Žije v Support, ne v modelu, protože našeptávání sahá do OBOU tabulek
 * najednou — z pohledu jednoho modelu by to byla cizí odpovědnost.
 */
final class ContentTags
{
    /**
     * Modely, které tagy nesou. Odsud se plní našeptávání.
     *
     * @var array<int, class-string<Article|Course>>
     */
    private const TAGGED_MODELS = [Article::class, Course::class];

    /**
     * Normalizuje jeden tag do kanonické podoby.
     *
     * Pravidlo: trim → sjednocení vnitřních mezer → malá písmena →
     * velké první písmeno.
     *
     * Snížení velikosti písmen je jediný deterministický způsob, jak
     * sloučit "GYMNASTIKA", "Gymnastika" a "gymnastika" do jednoho tagu.
     * Zpětné ucfirst() je tam proto, že tagy jsou uživatelsky viditelné
     * (chystaný tag cloud) a samá malá písmena by vypadala jako chyba.
     *
     * Funkce je idempotentní — opakované uložení tag nerozhýbe.
     */
    public static function normalize(string $tag): string
    {
        $tag = preg_replace('/\s+/u', ' ', $tag) ?? $tag;

        return Str::ucfirst(Str::lower(trim($tag)));
    }

    /**
     * Normalizuje celé pole tagů: zahodí prázdné a duplicity, které
     * po normalizaci splynuly, a přečísluje klíče (json list, ne objekt).
     *
     * @return array<int, string>
     */
    public static function normalizeMany(mixed $tags): array
    {
        if (! is_array($tags)) {
            return [];
        }

        $normalized = [];

        foreach ($tags as $tag) {
            if (! is_scalar($tag)) {
                continue;
            }

            $tag = self::normalize((string) $tag);

            if ($tag !== '') {
                $normalized[] = $tag;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * Výchozí tag pro nový příspěvek, nebo null.
     *
     * Chybějící i prázdný klíč jsou legitimní stav webu, který výchozí
     * tag nechce — ne chyba.
     */
    public static function defaultTag(): ?string
    {
        $tag = config('site.content.default_tag');

        if (! is_string($tag) || trim($tag) === '') {
            return null;
        }

        return self::normalize($tag);
    }

    /**
     * Všechny už použité tagy napříč VŠEMI modely, které je nesou.
     *
     * Sjednocení obou tabulek je záměr: bez něj by redaktor v článcích
     * neviděl tagy z kurzů a založil by "Dojo" podruhé jinak zapsané.
     *
     * @return array<int, string>
     */
    public static function suggestions(): array
    {
        $tags = [];

        foreach (self::TAGGED_MODELS as $model) {
            foreach ($model::query()->whereNotNull('tags')->pluck('tags') as $modelTags) {
                foreach ((array) $modelTags as $tag) {
                    $tags[] = $tag;
                }
            }
        }

        $tags = array_values(array_unique(array_filter($tags, 'is_string')));

        sort($tags, SORT_NATURAL | SORT_FLAG_CASE);

        return $tags;
    }
}
