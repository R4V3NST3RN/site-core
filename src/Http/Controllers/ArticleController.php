<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Course;
use Illuminate\Pagination\LengthAwarePaginator;

class ArticleController extends Controller
{
    public function index()
    {
        // Prázdný řetězec z ?sekce= se chová jako nevyplněný filtr, ne jako
        // sekce se jménem "" — jinak by odkaz s useknutou hodnotou vrátil
        // prázdný výpis místo celého feedu.
        $section = trim((string) request()->query('sekce', ''));
        $section = $section === '' ? null : $section;

        $articles = Article::published()
            ->when($section, fn ($q) => $q->where('category', $section))
            ->with('user')
            ->get()
            ->map(fn ($a) => [
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
                ->map(fn ($c) => [
                    'type' => 'course',
                    'item' => $c,
                    'date' => $c->published_at,
                ]);

        // Tři kritéria, protože ani dvě nestačí na deterministické pořadí:
        // datum samo nerozliší příspěvky publikované ve stejnou sekundu
        // a id je per-tabulka, takže článek #7 a kurz #7 se shodným datem
        // jsou v prvních dvou kritériích nerozlišitelné a rozhodla by
        // o nich databáze, která pořadí bez ORDER BY negarantuje. Typ to
        // uzavírá — dvojice (datum, id, typ) je napříč feedem unikátní.
        //
        // Fallback na created_at tu schválně není. Do mapy se dostane jen
        // obsah, který prošel filtrem published_at <= now(), takže 'date'
        // nikdy není null a NULL větev by byla mrtvý kód.
        $merged = $articles->concat($courses)
            ->sortBy([
                ['date', 'desc'],
                ['item.id', 'desc'],
                ['type', 'desc'],
            ])
            ->values();

        $perPage = 10;
        $currentPage = request()->get('page', 1);

        $feed = new LengthAwarePaginator(
            $merged->forPage($currentPage, $perPage),
            $merged->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('public.articles.index', compact('feed', 'section'));
    }

    public function show($slug)
    {
        $article = Article::published()
            ->where('slug', $slug)
            ->firstOrFail();

        $related = Article::published()
            ->where('id', '!=', $article->id)
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('public.articles.show', compact('article', 'related'));
    }
}
