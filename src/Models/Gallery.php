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

        // Číslování popisků patří sem, ne do formuláře. Hromadné nahrání volá
        // svůj callback jednou za SOUBOR (FilePond posílá fotky po jedné), takže
        // tam je počet fotek v dávce nezjistitelný a jmenovatel nikdy nesedl.
        // Při ukládání je naopak celé pole 'photos' pohromadě, takže "i/N" vyjde
        // správně. Jako u ostatních pravidel na modelu to navíc platí i pro
        // seedery, tinker a agenty zakládající obsah mimo admin.
        static::saving(function (self $gallery): void {
            $photos = $gallery->photos;

            if (blank($photos)) {
                return;
            }

            $title = trim((string) $gallery->title);

            // Bez názvu není z čeho popisek složit — holé "3/7" nikomu nepomůže,
            // tak radši necháme prázdno a redaktor si doplní vlastní.
            if ($title === '') {
                return;
            }

            $position = 0;
            $total = count($photos);

            foreach ($photos as $key => $photo) {
                $position++;

                // Vyplněný popisek se NEPŘEPISUJE, ani při pozdějším přidání fotek.
                // Redaktor si je ručně upravuje a hromadné přečíslování by mu tu
                // práci pokaždé zahodilo; proto starší řádky zůstanou na "4/4",
                // i když jich je mezitím šest.
                if (filled($photo['caption'] ?? null)) {
                    continue;
                }

                $photos[$key]['caption'] = $title.' '.$position.'/'.$total;
            }

            $gallery->photos = $photos;
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
