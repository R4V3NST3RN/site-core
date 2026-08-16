<?php

namespace App\Models;

use App\Enums\CourseEnrollmentState;
use App\Models\Concerns\HasTags;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasTags;
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
        'blocks',
        'terms',
        'tags',
        'perex',
        'featured_image',
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
        'published_at',
        'is_featured',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'last_synced_at' => 'datetime',
        'is_featured' => 'boolean',
        'blocks' => 'array',
        'terms' => 'array',
        'tags' => 'array',
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

    /**
     * Termíny, které teprve přijdou — surová podoba z API, jen
     * profiltrovaná časem.
     *
     * 'stamp' chodí z API jako STRING, proto přetypování na int;
     * bez něj by porovnání se now()->timestamp bylo nespolehlivé.
     *
     * Záměrně se nejmenuje remaining_lessons — tak se jmenuje ručně
     * plněný sloupec v DB a accessor stejného jména by ho zastínil.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUpcomingTermsAttribute(): array
    {
        $now = now()->timestamp;

        return array_values(array_filter(
            $this->terms ?? [],
            fn ($term) => is_array($term) && (int) ($term['stamp'] ?? 0) > $now,
        ));
    }

    /**
     * Počet zbývajících lekcí, počítaný z termínů — ne ze sloupce
     * remaining_lessons, který se plní ručně a zastarává.
     */
    public function getUpcomingLessonsCountAttribute(): int
    {
        return count($this->upcoming_terms);
    }

    /**
     * Cena za zbývající lekce. Null, když není známá cena za lekci —
     * nula by tvrdila, že je to zdarma.
     */
    public function getUpcomingLessonsPriceAttribute(): ?float
    {
        if (blank($this->price_per_lesson)) {
            return null;
        }

        return $this->upcoming_lessons_count * (float) $this->price_per_lesson;
    }

    /**
     * Už kurz běží? True, jakmile aspoň jeden termín proběhl.
     *
     * Rozhoduje o tom, která cena dává smysl: dokud kurz nezačal, je to
     * cena za celý kurz; jakmile běží, je to cena za zbývající lekce.
     *
     * Filtr na is_array() drží stejnou definici "termínu" jako
     * upcoming_terms — jinak by nevalidní položka spadla do proběhlých
     * a rozběhla by kurz, který ještě nezačal.
     */
    public function getHasStartedAttribute(): bool
    {
        $terms = array_filter($this->terms ?? [], fn ($term) => is_array($term));

        return count($terms) > $this->upcoming_lessons_count;
    }

    /**
     * Stav přihlašování. Odvozený, ne uložený — sloupec by zastaral ve chvíli,
     * kdy poslední termín utekl do minulosti, protože kurz nikdo neuloží.
     *
     * Neznámá kapacita (null) se počítá jako nula, tedy plno. Stejnou úvahu
     * dělá i show.blade.php a sidebar: tvrdit "je volno" bez dat by poslalo
     * zájemce na přihlášku, která ho odmítne.
     *
     * Prázdné/null terms je Unknown, ne Finished — čerstvě založený kurz bez
     * proběhlého syncu ještě nic neproběhlo, jen o něm nemáme data. Kdyby
     * to spadalo do Finished, detail i badge by tvrdily "skončil" o kurzu,
     * který se ani nerozjel.
     */
    public function getEnrollmentStateAttribute(): CourseEnrollmentState
    {
        if (blank($this->terms)) {
            return CourseEnrollmentState::Unknown;
        }

        if ($this->upcoming_lessons_count === 0) {
            return CourseEnrollmentState::Finished;
        }

        return ($this->available_spots ?? 0) > 0
            ? CourseEnrollmentState::Open
            : CourseEnrollmentState::Full;
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
