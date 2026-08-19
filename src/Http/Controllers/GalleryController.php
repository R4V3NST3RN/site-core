<?php

namespace App\Http\Controllers;

use App\Models\Gallery;

class GalleryController extends Controller
{
    public function index()
    {
        $galleries = Gallery::published()
            ->with('courseTypes')
            ->latest('published_at')
            ->paginate(10);

        return view('public.galleries.index', compact('galleries'));
    }

    public function show(string $slug)
    {
        $gallery = Gallery::published()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('public.galleries.show', compact('gallery'));
    }
}
