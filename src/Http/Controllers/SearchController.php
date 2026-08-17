<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Course;
use App\Models\Trainer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SearchController extends Controller
{
    /**
     * Stejná stránka jako blog feed — výsledky se řadí a stránkují
     * identicky, aby se obě stránky chovaly pro návštěvníka stejně.
     */
    private const PER_PAGE = 10;

    public function index()
    {
        $q = trim((string) request()->query('q', ''));

        // Prázdný dotaz není chyba, jen se nemá co hledat. View dostane
        // prázdný paginator (ne null), takže si nemusí hlídat typ.
        $results = $q === ''
            ? $this->paginate(collect())
            : $this->paginate($this->search($q));

        return view('public.search.index', compact('results', 'q'));
    }

    /**
     * Sjednocené výsledky ze všech tří zdrojů ve stejném tvaru jako
     * blog feed: ['type', 'item', 'date'].
     */
    private function search(string $q): Collection
    {
        // LOWER() na obou stranách je nejjednodušší zápis, který projde
        // na PostgreSQL i SQLite — ani jedna z nich nemá ILIKE společné.
        // Pozor: SQLite umí LOWER() jen pro ASCII, takže "Šeď" se tam
        // proti "šeď" netrefí. Na produkčním PostgreSQL, kde je LOWER()
        // závislý na locale, diakritika funguje.
        $needle = '%'.mb_strtolower($q).'%';

        $articles = Article::published()
            ->where(fn (Builder $w) => $w
                ->whereRaw('LOWER(title) LIKE ?', [$needle])
                ->orWhereRaw('LOWER(perex) LIKE ?', [$needle])
                ->orWhereRaw('LOWER(content) LIKE ?', [$needle])
                ->orWhereRaw('LOWER(category) LIKE ?', [$needle])
                ->orWhereRaw($this->tagsExpression(), [$needle])
            )
            ->with('user')
            ->get()
            ->map(fn (Article $a) => [
                'type' => 'article',
                'item' => $a,
                'date' => $a->published_at,
            ]);

        $courses = Course::published()
            ->where(fn (Builder $w) => $w
                ->whereRaw('LOWER(auto_title) LIKE ?', [$needle])
                ->orWhereRaw('LOWER(perex) LIKE ?', [$needle])
                ->orWhereRaw('LOWER(description) LIKE ?', [$needle])
                ->orWhereRaw($this->tagsExpression(), [$needle])
            )
            ->with(['courseType', 'trainer'])
            ->get()
            ->map(fn (Course $c) => [
                'type' => 'course',
                'item' => $c,
                'date' => $c->published_at,
            ]);

        // Trenéři nemají published scope — jejich veřejná viditelnost
        // se řídí jediným příznakem is_active.
        $trainers = Trainer::where('is_active', true)
            ->where(fn (Builder $w) => $w
                ->whereRaw('LOWER(first_name) LIKE ?', [$needle])
                ->orWhereRaw('LOWER(last_name) LIKE ?', [$needle])
                ->orWhereRaw('LOWER(bio) LIKE ?', [$needle])
                ->orWhereRaw('LOWER(specialization) LIKE ?', [$needle])
            )
            ->get()
            ->map(fn (Trainer $t) => [
                'type' => 'trainer',
                // Trenér nemá datum publikace, takže se do společného
                // řazení hlásí datem založení — jinak by neměl čím.
                'item' => $t,
                'date' => $t->created_at,
            ]);

        // Stejná tři kritéria jako v ArticleController::index(): datum
        // samo nerozliší záznamy ze stejné sekundy, id je per-tabulka,
        // a typ tu trojici uzavírá jako napříč výsledky unikátní.
        return $articles->concat($courses)->concat($trainers)
            ->sortBy([
                ['date', 'desc'],
                ['item.id', 'desc'],
                ['type', 'desc'],
            ])
            ->values();
    }

    /**
     * Tagy jsou json pole. Hledá se v jeho textové podobě, protože
     * rozbalovat pole v PHP by znamenalo natáhnout všechny řádky —
     * CAST na text projde na PostgreSQL (json → text) i na SQLite,
     * kde je sloupec stejně uložený jako TEXT.
     *
     * Cenou je, že se shoda trefí i do json syntaxe kolem hodnot;
     * u běžných dotazů (slovo, ne uvozovka) to nevadí.
     */
    private function tagsExpression(): string
    {
        return 'LOWER(CAST(tags AS TEXT)) LIKE ?';
    }

    private function paginate(Collection $items): LengthAwarePaginator
    {
        $currentPage = request()->get('page', 1);

        return (new LengthAwarePaginator(
            $items->forPage($currentPage, self::PER_PAGE),
            $items->count(),
            self::PER_PAGE,
            $currentPage,
            ['path' => request()->url()]
        ))->withQueryString();
    }
}
