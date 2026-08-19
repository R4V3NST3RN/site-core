<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Course;
use App\Models\Faq;
use App\Models\Gallery;
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

        // Příznak je výlučný (viz Gallery::booted()), takže first() vrací
        // jedinou vybranou galerii, nebo null, když žádná vybraná není.
        $homepageGallery = Gallery::published()
            ->where('show_on_homepage', true)
            ->first();

        return view('public.home', compact('featuredCourses', 'latestArticles', 'trainers', 'faqs', 'homepageGallery'));
    }
}
