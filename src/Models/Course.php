<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'course_type_id',
        'trainer_id',
        'day_of_week',
        'time_of_day',
        'start_time',
        'end_time',
        'months',
        'year',
        'description',
        'total_lessons',
        'remaining_lessons',
        'price_per_course',
        'price_per_lesson',
        'available_spots',
        'total_spots',
        'external_sync_id',
        'last_synced_at',
        'slug',
        'auto_title',
        'status',
        'is_featured',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'last_synced_at' => 'datetime',
        'is_featured' => 'boolean',
        'price_per_course' => 'decimal:2',
        'price_per_lesson' => 'decimal:2',
    ];

    // Vztahy
    public function courseType()
    {
        return $this->belongsTo(CourseType::class);
    }

    public function trainer()
    {
        return $this->belongsTo(Trainer::class);
    }

    // Auto-generování názvu podle vzorce
    // [Typ kurzu], vede [Trenér], [den] [denní doba] - [měsíce] [rok]
    public function generateAutoTitle(): string
    {
        $parts = [];

        // Typ kurzu (s věkovou kategorií pokud je pro děti)
        if ($this->courseType) {
            $parts[] = $this->courseType->full_name;
        }

        // Trenér
        if ($this->trainer) {
            $parts[] = "vede {$this->trainer->full_name}";
        }

        // Den a denní doba
        if ($this->day_of_week && $this->time_of_day) {
            $parts[] = "{$this->day_of_week} {$this->time_of_day}";
        }

        // Měsíce a rok
        if ($this->months && $this->year) {
            $parts[] = "{$this->months} {$this->year}";
        }

        return implode(', ', $parts);
    }
}
