<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Trainer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'slug',
        'photo',
        'bio',
        'specialization',
        'email',
        'phone',
        'is_active',
        'order',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($trainer) {
            if (empty($trainer->slug)) {
                $trainer->slug = Str::slug($trainer->first_name.' '.$trainer->last_name);
            }
        });
    }

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Accessor - celé jméno
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
