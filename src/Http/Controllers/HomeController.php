<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Article;
use App\Models\Trainer;

class HomeController extends Controller
{
    public function index()
    {
        $featuredCourses = Course::where('status', 'active')
            ->with(['courseType', 'trainer'])
            ->latest()
            ->take(6)
            ->get();

        $latestArticles = Article::where('status', 'published')
            ->latest('published_at')
            ->take(3)
            ->get();

        $trainers = Trainer::where('is_active', true)
            ->orderBy('order')
            ->take(config('site.home.featured_trainers_count'))
            ->get();

        return view('public.home', compact('featuredCourses', 'latestArticles', 'trainers'));
    }
}

