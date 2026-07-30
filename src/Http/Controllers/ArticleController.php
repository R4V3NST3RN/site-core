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
            ->with('user')
            ->get()
            ->map(fn($a) => [
                'type' => 'article',
                'item' => $a,
                'date' => $a->published_at ?? $a->created_at,
            ]);

        $courses = Course::where('status', 'active')
            ->with(['courseType', 'trainer'])
            ->get()
            ->map(fn($c) => [
                'type' => 'course',
                'item' => $c,
                'date' => $c->created_at,
            ]);

        $merged = $articles->concat($courses)
            ->sortByDesc('date')
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
            ->firstOrFail();

        $related = Article::where('status', 'published')
            ->where('id', '!=', $article->id)
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('public.articles.show', compact('article', 'related'));
    }
}
