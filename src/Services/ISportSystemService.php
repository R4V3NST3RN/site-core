<?php

namespace App\Services;

use App\Contracts\CourseSyncProvider;
use App\Models\Course;
use App\Support\SyncResult;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Integrace na iSportSystem.
 *
 * Adresa API je per-web konfigurace (config('site.sync.base_url')) —
 * prázdná hodnota znamená vypnutou integraci, ne chybu. Weby, které
 * iSportSystem nepoužívají, mají v config('site.sync.provider')
 * NullSyncProvider a tuhle třídu nikdy neinstanciují.
 */
class ISportSystemService implements CourseSyncProvider
{
    protected string $baseUrl;

    /**
     * Kurzy z API indexované podle id_course. API vrací celý seznam
     * jednou hláškou, takže se za život instance stahuje jen jednou —
     * jinak by dávková synchronizace volala API pro každý kurz zvlášť.
     *
     * @var array<string, array<string, mixed>>|null
     */
    protected ?array $courseIndex = null;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = rtrim($baseUrl ?? (string) config('site.sync.base_url', ''), '/');
    }

    public function enabled(): bool
    {
        return $this->baseUrl !== '';
    }

    /**
     * Získá seznam všech kurzů z iSportSystem
     */
    public function getCourses(?string $fromDate = null): array
    {
        if (! $this->enabled()) {
            return [];
        }

        try {
            if (! $fromDate) {
                $fromDate = now()->subYear()->format('Ymd');
            }

            $url = $this->baseUrl.'/courses.php?date='.$fromDate;

            $response = Http::withoutVerifying()->get($url);

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            Log::error('iSportSystem API error: '.$response->status());

            return [];

        } catch (\Exception $e) {
            Log::error('iSportSystem connection error: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Získá detail konkrétního kurzu podle externího ID
     */
    public function getCourse(string $isportId): ?array
    {
        return $this->courseIndex()[$isportId] ?? null;
    }

    public function sync(Course $course): SyncResult
    {
        if (! $this->enabled()) {
            return SyncResult::skipped('Sync provider není aktivní.');
        }

        $externalId = $course->external_sync_id;

        if (blank($externalId)) {
            return SyncResult::skipped('Kurz nemá přiřazené externí ID.');
        }

        $data = $this->getCourse((string) $externalId);

        if (! $data) {
            Log::warning("iSportSystem: Kurz s ID {$externalId} nebyl nalezen.");

            return SyncResult::failure(
                "Kurz s ID {$externalId} nebyl v iSportSystem nalezen.",
                (string) $externalId,
            );
        }

        try {
            $course->update([
                'available_spots' => $data['available'] ?? $course->available_spots,
                'total_spots' => $data['capacity'] ?? $course->total_spots,
                'total_lessons' => $data['number_lessons'] ?? $course->total_lessons,
                'price_per_lesson' => $data['price'] ?? $course->price_per_lesson,
                // Termíny se ukládají SUROVÉ, tak jak přišly. Parsování
                // (stamp -> datum a čas) patří až do renderu, ať je změna
                // formátu API jen na jednom místě.
                //
                // Mapuje se výhradně 'terms'. Odpověď API nese i
                // trainer_name a trainer_image, což jsou osobní údaje
                // reálných trenérů — ty se na tenhle web ukládat NESMÍ,
                // viz DECISIONS.md, sekce o reálné iSport API.
                'terms' => $data['terms'] ?? $course->terms,
                'last_synced_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('iSportSystem sync error: '.$e->getMessage());

            return SyncResult::failure(
                'Zápis synchronizovaných dat selhal: '.$e->getMessage(),
                (string) $externalId,
            );
        }

        return SyncResult::success((string) $externalId);
    }

    public function lastSyncedAt(Course $course): ?CarbonImmutable
    {
        return $course->last_synced_at
            ? CarbonImmutable::instance($course->last_synced_at)
            : null;
    }

    /**
     * Získá nejbližší aktivity (lekce)
     */
    public function getActivities(): array
    {
        if (! $this->enabled()) {
            return [];
        }

        try {
            $response = Http::withoutVerifying()
                ->get($this->baseUrl.'/activities.php');

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            return [];

        } catch (\Exception $e) {
            Log::error('iSportSystem activities error: '.$e->getMessage());

            return [];
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function courseIndex(): array
    {
        if ($this->courseIndex === null) {
            $this->courseIndex = [];

            foreach ($this->getCourses() as $isportCourse) {
                if (isset($isportCourse['id_course'])) {
                    $this->courseIndex[(string) $isportCourse['id_course']] = $isportCourse;
                }
            }
        }

        return $this->courseIndex;
    }
}
