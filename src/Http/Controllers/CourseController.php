<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseType;
use App\Services\BlogFeed;

class CourseController extends Controller
{
    public function __construct(private readonly BlogFeed $feed) {}

    public function index()
    {
        $courseTypes = CourseType::where('is_active', true)
            // Počet u typu musí sedět s tím, co návštěvník po rozkliknutí
            // uvidí — jinak by typ sliboval kurzy, které jsou ještě skryté.
            ->withCount(['courses' => fn ($q) => $q->published()])
            ->orderBy('order')
            ->get();

        $courses = Course::published()
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

        $course = Course::published()
            ->where('slug', $courseSlug)
            ->where('course_type_id', $courseType->id)
            ->with(['courseType', 'trainer'])
            ->firstOrFail();

        $related = $this->feed->related($course, 3);

        ['previous' => $previous, 'next' => $next] = $this->feed->neighbours($course);

        return view('public.courses.show', compact('course', 'related', 'previous', 'next'));
    }
}
