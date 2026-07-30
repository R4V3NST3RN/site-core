<?php

namespace App\Contracts;

use App\Models\Course;
use App\Support\SyncResult;
use Carbon\CarbonImmutable;

/**
 * Synchronizace kurzu s externím rezervačním systémem.
 *
 * Sdílené jádro smí znát jen tohle rozhraní — konkrétní klientská
 * integrace (iSportSystem) i sandboxový no-op jsou per-web volba,
 * která se dělá v config('site.sync.provider'). Viz PORTING-NOTES P1.
 */
interface CourseSyncProvider
{
    /**
     * Má tenhle web vůbec s čím synchronizovat?
     *
     * Neaktivní provider není chyba — je to legitimní stav webu,
     * který externí systém nepoužívá.
     */
    public function enabled(): bool;

    /**
     * Synchronizuje jeden kurz. Nikdy nevyhazuje výjimku —
     * výsledek (včetně selhání) se vrací v SyncResult.
     */
    public function sync(Course $course): SyncResult;

    /**
     * Kdy byl kurz naposledy synchronizován, nebo null.
     */
    public function lastSyncedAt(Course $course): ?CarbonImmutable;
}
