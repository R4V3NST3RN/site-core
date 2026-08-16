<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseType;

class CourseController extends Controller
{
    public function index()
    {
        $courseTypes = CourseType::where('is_active', true)
            // Počet u typu musí sedět s tím, co návštěvník po rozkliknutí
            // uvidí — jinak by typ sliboval kurzy, které jsou ještě skryté.
            ->withCount(['courses' => fn ($q) => $q->where('status', 'active')
                ->where('published_at', '<=', now())])
            ->orderBy('order')
            ->get();

        $courses = Course::where('status', 'active')
            ->where('published_at', '<=', now())
            ->with(['courseType', 'trainer'])
            ->orderBy('auto_title')
            ->paginate(12);

        return view('public.courses.index', compact('courses', 'courseTypes'));
    }

    public function show(string $typeSlug, string $courseSlug)
    {
        $courseType = CourseType::where('slug', $typeSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $course = Course::where('slug', $courseSlug)
            ->where('course_type_id', $courseType->id)
            ->where('status', 'active')
            ->where('published_at', '<=', now())
            ->with(['courseType', 'trainer'])
            ->firstOrFail();

        return view('public.courses.show', compact('course'));
    }
}
