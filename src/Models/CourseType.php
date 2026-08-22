<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    /**
     * Aktivní typy kurzů v pořadí, v jakém se mají zobrazit.
     *
     * Protějšek Trainer::activeOrdered() — stejná dvojice podmínek, stejný
     * důvod: dotaz na typy do hlavičky si nesmí žít vlastním životem vedle
     * ostatních výpisů. Řazení podle 'order' drží ruční pořadí z adminu.
     */
    public function scopeActiveOrdered(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->orderBy('order');
    }

    // Vztah - typ kurzu má mnoho kurzů
    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    // Vztah - galerie přiřazené k tomuto typu kurzu (M:N)
    public function galleries(): BelongsToMany
    {
        return $this->belongsToMany(Gallery::class);
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
