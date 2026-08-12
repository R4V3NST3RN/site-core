<?php

namespace App\Models;

use App\Models\Concerns\HasTags;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use HasTags;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'perex',
        'content',
        'blocks',
        'template',
        'category',
        'tags',
        'featured_image',
        'user_id',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'blocks' => 'array',
        'tags' => 'array',
    ];

    // Vztah - článek patří uživateli (autorovi)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
