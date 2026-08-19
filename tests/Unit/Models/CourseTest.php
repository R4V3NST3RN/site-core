<?php

namespace Tests\Unit\Models;

use App\Models\Course;
use App\Models\Trainer;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

/**
 * Unit test pro App\Models\Course.
 *
 * Testuje definici modelu (fillable, casty, SoftDeletes). Dědí z Tests\TestCase,
 * protože fill() spouští datetime cast, který sahá na getConnection() —
 * bez bootnuté aplikace (Tests\TestCase) test spadne i bez zápisu do DB.
 */
class CourseTest extends TestCase
{
    /**
     * Očekávaný seznam hromadně přiřaditelných (fillable) atributů podle
     * app/Models/Course.php. Pokud se tento test rozbije po úpravě modelu,
     * je to signál, že se změnila veřejná mass-assignment plocha modelu
     * a je potřeba to reflektovat i jinde (Form Requesty, Filament resource).
     *
     * Seznam je schválně vyjmenovaný celý, ne jen namátkou klíčová pole:
     * fillable je bezpečnostní plocha (Course plní i sync z cizího API),
     * takže každý přírůstek má projít vědomým schválením. Kontrola na
     * pouhou přítomnost pár polí by nový sloupec propustila mlčky.
     */
    public function test_has_expected_fillable_attributes(): void
    {
        $expected = [
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

        $course = new Course;

        $this->assertEqualsCanonicalizing($expected, $course->getFillable());
    }

    public function test_mass_assignment_ignores_non_fillable_attributes(): void
    {
        $course = new Course;

        $course->fill([
            'day_of_week' => 'pondělí',
            'status' => 'active',
            // 'id' a časová razítka nejsou fillable, mass assignment je musí ignorovat.
            'id' => 999,
            'created_at' => '2000-01-01 00:00:00',
        ]);

        $this->assertSame('pondělí', $course->day_of_week);
        $this->assertSame('active', $course->status);
        $this->assertArrayNotHasKey('id', $course->getAttributes());
        $this->assertArrayNotHasKey('created_at', $course->getAttributes());
    }

    public function test_mass_assignment_accepts_every_declared_fillable_attribute(): void
    {
        $course = new Course;

        $payload = [
            'course_type_id' => 1,
            'trainer_id' => 2,
            'day_of_week' => 'úterý',
            'time_of_day' => 'dopoledne',
            'start_time' => '2026-09-01 09:00:00',
            'end_time' => '2026-09-01 10:00:00',
            'months' => 'září-prosinec',
            'year' => 2026,
            'description' => 'Testovací popis kurzu',
            'blocks' => [['type' => 'rich_text', 'data' => ['content' => 'Blok']]],
            'terms' => [['stamp' => '1790000000']],
            'tags' => ['Dojo'],
            'perex' => 'Krátký perex kurzu',
            'featured_image' => 'courses/nahled.webp',
            'total_lessons' => 12,
            'remaining_lessons' => 12,
            'price_per_course' => 1500,
            'price_per_lesson' => 125,
            'available_spots' => 10,
            'total_spots' => 12,
            'external_sync_id' => 'EXT-123',
            'last_synced_at' => '2026-07-01 12:00:00',
            'slug' => 'testovaci-kurz',
            'auto_title' => 'Testovací kurz',
            'status' => 'active',
            'published_at' => '2026-07-01 08:00:00',
            'is_featured' => true,
        ];

        $course->fill($payload);

        foreach (array_keys($payload) as $attribute) {
            $this->assertArrayHasKey(
                $attribute,
                $course->getAttributes(),
                "Fillable atribut '{$attribute}' se nepodařilo hromadně přiřadit."
            );
        }
    }

    /**
     * N6: generateAutoTitle() musí jméno trenéra skládat v 1. pádě
     * ("vede Marcus Vlk"), ne v nesprávném 7. pádě ("s Marcus Vlk") -
     * algoritmické skloňování cizích/nestandardních jmen by bylo nespolehlivé.
     */
    public function test_generate_auto_title_uses_first_case_for_trainer_name(): void
    {
        $course = new Course;
        $course->setRelation('trainer', new Trainer([
            'first_name' => 'Marcus',
            'last_name' => 'Vlk',
        ]));

        $title = $course->generateAutoTitle();

        $this->assertStringContainsString('vede Marcus Vlk', $title);
        $this->assertStringNotContainsString('s Marcus Vlk', $title);
    }

    public function test_uses_soft_deletes(): void
    {
        $this->assertContains(SoftDeletes::class, class_uses(Course::class));
    }
}
