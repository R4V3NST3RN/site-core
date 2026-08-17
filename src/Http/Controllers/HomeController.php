<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Course;
use App\Models\Faq;
use App\Models\Trainer;

class HomeController extends Controller
{
    public function index()
    {
        $featuredCourses = Course::published()
            ->with(['courseType', 'trainer'])
            ->latest()
            ->take(6)
            ->get();

        $latestArticles = Article::published()
            ->latest('published_at')
            ->take(3)
            ->get();

        $trainers = Trainer::where('is_active', true)
            ->orderBy('order')
            ->get();

        $faqs = Faq::where('is_active', true)
            ->orderBy('order')
            ->get();

        return view('public.home', compact('featuredCourses', 'latestArticles', 'trainers', 'faqs'));
    }
}
