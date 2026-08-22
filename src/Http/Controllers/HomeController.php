<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Course;
use App\Models\Faq;

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

        $trainers = $this->allTrainers();

        $faqs = Faq::where('is_active', true)
            ->orderBy('order')
            ->get();

        $homepageGallery = $this->homepageGallery();

        $partner = $this->activePartner();

        return view('public.home', compact('featuredCourses', 'latestArticles', 'trainers', 'faqs', 'homepageGallery', 'partner'));
    }
}
