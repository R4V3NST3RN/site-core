<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\BlogFeed;
use Illuminate\Pagination\LengthAwarePaginator;

class ArticleController extends Controller
{
    public function __construct(private readonly BlogFeed $feed) {}

    public function index()
    {
        // Prázdný řetězec z ?sekce= se chová jako nevyplněný filtr, ne jako
        // sekce se jménem "" — jinak by odkaz s useknutou hodnotou vrátil
        // prázdný výpis místo celého feedu.
        $section = trim((string) request()->query('sekce', ''));
        $section = $section === '' ? null : $section;

        $merged = $this->feed->entries($section);

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

        $related = $this->feed->related($article, 3);

        ['previous' => $previous, 'next' => $next] = $this->feed->neighbours($article);

        return view('public.articles.show', compact('article', 'related', 'previous', 'next'));
    }
}
