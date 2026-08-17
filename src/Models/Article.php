<?php

namespace App\Models;

use App\Models\Concerns\HasTags;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Obsah viditelný veřejnosti: publikovaný a s datem, které už nastalo.
     *
     * Obě podmínky patří k sobě — samotný status 'published' pustí ven
     * i článek naplánovaný na příští týden. Scope je jedno místo, kde
     * se ta dvojice drží, aby se nerozešla mezi controllery.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where('published_at', '<=', now());
    }

    // Vztah - článek patří uživateli (autorovi)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
