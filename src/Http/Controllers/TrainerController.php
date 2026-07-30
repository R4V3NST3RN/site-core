<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Trainer;

class TrainerController extends Controller
{
    public function index()
    {
        $trainers = Trainer::where('is_active', true)
            ->orderBy('first_name')
            ->get();

        return view('public.trainers.index', compact('trainers'));
    }

    public function show(string $slug)
    {
        $trainer = Trainer::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $courses = Course::where('trainer_id', $trainer->id)
            ->where('status', 'active')
            ->with('courseType')
            ->orderBy('start_time')
            ->get();

        return view('public.trainers.show', compact('trainer', 'courses'));
    }
}
