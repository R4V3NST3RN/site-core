<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Course;
use Illuminate\Pagination\LengthAwarePaginator;

class ArticleController extends Controller
{
    public function index()
    {
        $articles = Article::where('status', 'published')
            ->where('published_at', '<=', now())
            ->with('user')
            ->get()
            ->map(fn ($a) => [
                'type' => 'article',
                'item' => $a,
                'date' => $a->published_at,
            ]);

        $courses = Course::where('status', 'active')
            ->where('published_at', '<=', now())
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

        $perPage = 9;
        $currentPage = request()->get('page', 1);

        $feed = new LengthAwarePaginator(
            $merged->forPage($currentPage, $perPage),
            $merged->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('public.articles.index', compact('feed'));
    }

    public function show($slug)
    {
        $article = Article::where('slug', $slug)
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->firstOrFail();

        $related = Article::where('status', 'published')
            ->where('published_at', '<=', now())
            ->where('id', '!=', $article->id)
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('public.articles.show', compact('article', 'related'));
    }
}
