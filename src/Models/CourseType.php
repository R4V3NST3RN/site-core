<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'age_category',
        'is_for_children',
        'is_active',
        'order',
        'hero_image',
        'list_image',
    ];

    protected $casts = [
        'is_for_children' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Vztah - typ kurzu má mnoho kurzů
    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    // Accessor - plný název (s věkovou kategorií, pokud je pro děti)
    public function getFullNameAttribute(): string
    {
        if ($this->is_for_children && $this->age_category) {
            return "{$this->name} ({$this->age_category})";
        }

        return $this->name;
    }
}
