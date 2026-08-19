<?php

namespace App\Services;

use App\Enums\CourseEnrollmentState;
use App\Models\Article;
use App\Models\Course;
use App\Support\ContentTags;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Blogový feed — jeden sjednocený proud článků a kurzů.
 *
 * Logika sem přišla z ArticleController::index(), protože ji vedle výpisu
 * potřebují i detaily (sousedi pro předchozí/další a doporučené příspěvky).
 * Kdyby zůstala v controlleru, musela by se na dalších dvou místech
 * zkopírovat a řazení feedu by se časem rozešlo.
 *
 * Položka feedu je pole ['type' => 'article'|'course', 'item' => Model,
 * 'date' => published_at] — stejný tvar, jaký konzumují Blade partialy.
 */
class BlogFeed
{
    /**
     * Feed načtený v rámci jedné instance, klíčovaný filtrem sekce.
     *
     * Detail stránky volá neighbours() i related() za sebou a obě potřebují
     * celý feed; bez téhle paměti by se stejné dva dotazy pustily dvakrát.
     *
     * @var array<string, Collection>
     */
    private array $cache = [];

    /**
     * Sjednocený seřazený feed článků a kurzů.
     *
     * @param  string|null  $section  Sekce (kategorie článku). Když je zadaná,
     *                                vrací JEN články té sekce — kurzy sekci
     *                                nemají, takže z feedu mizí celé.
     * @return Collection<int, array{type: string, item: Model, date: mixed}>
     */
    public function entries(?string $section = null): Collection
    {
        $key = $section ?? '';

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $articles = Article::published()
            ->when($section, fn ($q) => $q->where('category', $section))
            ->with('user')
            ->get()
            ->map(fn (Article $a) => [
                'type' => 'article',
                'item' => $a,
                'date' => $a->published_at,
            ]);

        // Sekce je vlastnost článků; kurzy žádnou nemají, takže při zvolené
        // sekci z feedu mizí celé. Míchat "sekce Akce" s kurzy by znamenalo
        // tvrdit, že kurz do té sekce patří.
        $courses = $section !== null
            ? collect()
            : Course::published()
                ->with(['courseType', 'trainer'])
                ->get()
                ->map(fn (Course $c) => [
                    'type' => 'course',
                    'item' => $c,
                    'date' => $c->published_at,
                ]);

        // Tři kritéria, protože ani dvě nestačí na deterministické pořadí:
        // datum samo nerozliší příspěvky publikované ve stejnou sekundu
        // a id je per-tabulka, takže článek #7 a kurz #7 se shodným datem
        // jsou v prvních dvou kritériích nerozlišitelné a rozhodla by
        // o nich databáze, která pořadí bez ORDER BY negarantuje. Typ to
        // uzavírá — trojice (datum, id, typ) je napříč feedem unikátní.
        //
        // Fallback na created_at tu schválně není. Do mapy se dostane jen
        // obsah, který prošel filtrem published_at <= now(), takže 'date'
        // nikdy není null a NULL větev by byla mrtvý kód.
        return $this->cache[$key] = $articles->concat($courses)
            ->sortBy([
                ['date', 'desc'],
                ['item.id', 'desc'],
                ['type', 'desc'],
            ])
            ->values();
    }

    /**
     * Sousedé zadaného příspěvku ve feedu.
     *
     * Feed je řazený od nejnovějšího, takže položka PŘED zadanou je novější
     * ('next') a položka ZA ní starší ('previous'). U nejnovějšího příspěvku
     * je 'next' null, u nejstaršího 'previous' null.
     *
     * @return array{previous: ?array, next: ?array}
     */
    public function neighbours(Model $item): array
    {
        $entries = $this->entries();

        $index = $entries->search(fn (array $entry) => $this->isSame($entry, $item));

        // Příspěvek ve feedu není (např. kurz, který mezitím přestal být
        // publikovaný) — pak nemá smysl tvrdit, kdo je jeho soused.
        if ($index === false) {
            return ['previous' => null, 'next' => null];
        }

        return [
            'previous' => $entries->get($index + 1),
            'next' => $entries->get($index - 1),
        ];
    }

    /**
     * Doporučené příspěvky k zadanému, řazené podle počtu shodných tagů.
     *
     * Bez jediné shody je skóre 0 — taková položka z výběru nevypadne, jen
     * klesne pod ty se shodou. Díky tomu se seznam vždycky dorovná
     * nejnovějšími příspěvky z feedu, i když tagy nesedí vůbec.
     *
     * @return Collection<int, array{type: string, item: Model, date: mixed}>
     */
    public function related(Model $item, int $limit = 3): Collection
    {
        $tags = $this->normalizedTags($item);

        $candidates = $this->entries()
            ->reject(fn (array $entry) => $this->isSame($entry, $item))
            ->reject(function (array $entry) {
                // Vyřazujeme jen kurzy, o kterých VÍME, že doběhly.
                // Unknown (prázdné terms, kurz čeká na první sync) tu zůstává
                // schválně: tam nám chybí data, netvrdíme, že kurz skončil —
                // zamlčet ho by znamenalo trestat kurz za neproběhlý sync.
                return $entry['item'] instanceof Course
                    && $entry['item']->enrollment_state === CourseEnrollmentState::Finished;
            });

        // sortByDesc je od PHP 8.0 stabilní, takže při shodném skóre zůstává
        // zachované pořadí feedu (datum desc) — žádné druhé kritérium netřeba.
        return $candidates
            ->sortByDesc(fn (array $entry) => $this->tagOverlap($entry['item'], $tags))
            ->take($limit)
            ->values();
    }

    /**
     * Je položka feedu tentýž záznam jako zadaný model?
     *
     * Porovnává typ i id — id je per-tabulka, takže článek #7 a kurz #7
     * jsou dva různé příspěvky se stejným číslem.
     */
    private function isSame(array $entry, Model $item): bool
    {
        return $entry['type'] === $this->typeOf($item)
            && $entry['item']->getKey() === $item->getKey();
    }

    private function typeOf(Model $item): string
    {
        return $item instanceof Article ? 'article' : 'course';
    }

    /**
     * Počet tagů, které má položka společné se zadanou sadou.
     */
    private function tagOverlap(Model $candidate, array $tags): int
    {
        if ($tags === []) {
            return 0;
        }

        return count(array_intersect($tags, $this->normalizedTags($candidate)));
    }

    /**
     * Tagy modelu v kanonické podobě.
     *
     * Normalizace je stejná jako v HasTags při ukládání, takže u čerstvých
     * záznamů nic nemění. Je tu kvůli řádkům uloženým dřív, než trait vznikl —
     * bez ní by se "dojo" a "Dojo" nepotkaly a shoda by tiše vypadla.
     *
     * @return array<int, string>
     */
    private function normalizedTags(Model $candidate): array
    {
        return ContentTags::normalizeMany($candidate->tags ?? []);
    }
}
