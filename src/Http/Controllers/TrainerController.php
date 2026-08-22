<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Trainer;

class TrainerController extends Controller
{
    public function index()
    {
        $trainers = $this->allTrainers();

        $homepageGallery = $this->homepageGallery();

        $partner = $this->activePartner();

        return view('public.trainers.index', compact('trainers', 'homepageGallery', 'partner'));
    }

    public function show(string $slug)
    {
        $trainer = Trainer::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $courses = Course::published()
            ->where('trainer_id', $trainer->id)
            ->with('courseType')
            ->orderBy('start_time')
            ->get();

        // Bez trenéra, jehož profil je právě otevřený — ve sdíleném bloku
        // by se jinak objevil podruhé hned pod svým vlastním detailem.
        $trainers = $this->allTrainers($trainer->id);

        $homepageGallery = $this->homepageGallery();

        $partner = $this->activePartner();

        return view('public.trainers.show', compact('trainer', 'courses', 'trainers', 'homepageGallery', 'partner'));
    }
}
