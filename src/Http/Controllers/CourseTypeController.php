<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseType;

class CourseTypeController extends Controller
{
    public function show(string $typeSlug)
    {
        $courseType = CourseType::where('slug', $typeSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $courses = Course::published()
            ->where('course_type_id', $courseType->id)
            ->with(['trainer', 'courseType'])
            ->orderBy('start_time')
            ->get();

        return view('public.course_types.show', compact('courseType', 'courses'));
    }
}
