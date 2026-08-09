<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Faq extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'question',
        'slug',
        'answer',
        'is_active',
        'order',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($faq) {
            if (empty($faq->slug)) {
                $faq->slug = Str::slug($faq->question);
            }
        });
    }

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
