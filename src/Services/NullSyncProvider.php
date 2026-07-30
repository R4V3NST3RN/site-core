<?php

namespace App\Services;

use App\Contracts\CourseSyncProvider;
use App\Models\Course;
use App\Support\SyncResult;
use Carbon\CarbonImmutable;

/**
 * Provider pro weby bez externího rezervačního systému.
 *
 * Nic nedělá a nic nehlásí — žádné výjimky, žádné chybové logy.
 * "Nemám s čím synchronizovat" je normální provozní stav, ne selhání.
 */
class NullSyncProvider implements CourseSyncProvider
{
    public function enabled(): bool
    {
        return false;
    }

    public function sync(Course $course): SyncResult
    {
        return SyncResult::skipped('Sync provider není aktivní.');
    }

    public function lastSyncedAt(Course $course): ?CarbonImmutable
    {
        return null;
    }
}
