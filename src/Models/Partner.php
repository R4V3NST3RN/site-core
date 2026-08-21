<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partner extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'url',
        'products',
        'is_active',
        'order',
        'status',
        'published_at',
    ];

    protected $casts = [
        'products' => 'array',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Výlučnost příznaku "zobrazit na webu" visí na modelové události,
     * ne na Filament formuláři — stejná úvaha jako u Gallery: pravidlo
     * pak platí i pro seedery, tinker a agenty zakládající obsah mimo admin.
     */
    protected static function booted(): void
    {
        static::saving(function (self $partner): void {
            if (! $partner->is_active) {
                return;
            }

            // Query builder update, ne Eloquent save na ostatních záznamech:
            // save() by u každého spustil znovu tenhle saving hook a vzniklo
            // by zacyklení. Tahle cesta navíc obejde model events úplně,
            // takže se nespustí ani žádný cizí listener.
            //
            // exists chrání nový záznam: ten ještě nemá id, takže by se
            // podmínka '!=' null nechytila a partner by vypnul i sebe.
            static::query()
                ->when($partner->exists, fn (Builder $q) => $q->whereKeyNot($partner->getKey()))
                ->where('is_active', true)
                ->update(['is_active' => false]);
        });
    }

    /**
     * Partneři viditelní veřejnosti: publikovaní a s datem, které už nastalo.
     *
     * Stejná dvojice podmínek jako Gallery::published() — samotný status
     * 'published' pustí ven i partnera naplánovaného na příští týden.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where('published_at', '<=', now());
    }

    /**
     * Počet produktů — pro sloupec v adminu.
     *
     * Prázdné i nevyplněné products dávají nulu; null větev tu být musí,
     * protože sloupec je nullable a count(null) by byl TypeError.
     */
    public function getProductCountAttribute(): int
    {
        return count($this->products ?? []);
    }
}
