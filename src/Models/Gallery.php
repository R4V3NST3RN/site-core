<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gallery extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'cover_image',
        'photos',
        'show_on_homepage',
        'status',
        'published_at',
    ];

    protected $casts = [
        'photos' => 'array',
        'show_on_homepage' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Výlučnost příznaku "na hlavní straně" visí na modelové události,
     * ne na Filament formuláři — stejná úvaha jako u HasTags: pravidlo
     * pak platí i pro seedery, tinker a agenty zakládající obsah mimo admin.
     */
    protected static function booted(): void
    {
        static::saving(function (self $gallery): void {
            if (! $gallery->show_on_homepage) {
                return;
            }

            // Query builder update, ne Eloquent save na ostatních záznamech:
            // save() by u každého spustil znovu tenhle saving hook a vzniklo
            // by zacyklení. Tahle cesta navíc obejde model events úplně,
            // takže se nespustí ani žádný cizí listener.
            //
            // exists chrání nový záznam: ten ještě nemá id, takže by se
            // podmínka '!=' null nechytila a galerie by vypnula i sebe.
            static::query()
                ->when($gallery->exists, fn (Builder $q) => $q->whereKeyNot($gallery->getKey()))
                ->where('show_on_homepage', true)
                ->update(['show_on_homepage' => false]);
        });
    }

    /**
     * Galerie viditelné veřejnosti: publikované a s datem, které už nastalo.
     *
     * Stejná dvojice podmínek jako Article::published() — samotný status
     * 'published' pustí ven i galerii naplánovanou na příští týden.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where('published_at', '<=', now());
    }

    /**
     * Typy kurzů, na jejichž stránce se galerie navíc zobrazí.
     */
    public function courseTypes(): BelongsToMany
    {
        return $this->belongsToMany(CourseType::class);
    }

    /**
     * Počet fotek — pro popisky typu "1/14" a sloupec v adminu.
     *
     * Prázdné i nevyplněné photos dávají nulu; null větev tu být musí,
     * protože sloupec je nullable a count(null) by byl TypeError.
     */
    public function getPhotoCountAttribute(): int
    {
        return count($this->photos ?? []);
    }
}
