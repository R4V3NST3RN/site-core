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

        // Galerie přiřazené tomuhle typu kurzu. Filtr published() musí být
        // i tady — vazba sama nic o viditelnosti neříká, takže bez něj by
        // se na veřejný web dostal koncept.
        $galleries = $courseType->galleries()
            ->published()
            ->latest('published_at')
            ->get();

        $trainers = $this->allTrainers();

        $homepageGallery = $this->homepageGallery();

        $partner = $this->activePartner();

        return view('public.course_types.show', compact('courseType', 'courses', 'galleries', 'trainers', 'homepageGallery', 'partner'));
    }
}
