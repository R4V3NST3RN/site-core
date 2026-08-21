<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Trainer;

class TrainerController extends Controller
{
    public function index()
    {
        $trainers = Trainer::where('is_active', true)
            ->orderBy('order')
            ->get();

        $partner = $this->activePartner();

        return view('public.trainers.index', compact('trainers', 'partner'));
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

        $partner = $this->activePartner();

        return view('public.trainers.show', compact('trainer', 'courses', 'partner'));
    }
}
