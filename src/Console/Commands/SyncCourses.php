<?php

namespace App\Console\Commands;

use App\Contracts\CourseSyncProvider;
use App\Models\Course;
use Illuminate\Console\Command;

class SyncCourses extends Command
{
    protected $signature = 'courses:sync';

    protected $description = 'Synchronizuje data kurzů s externím rezervačním systémem';

    public function handle(CourseSyncProvider $provider): int
    {
        if (! $provider->enabled()) {
            $this->info('Sync provider není aktivní.');

            return self::SUCCESS;
        }

        $this->info('Spouštím synchronizaci kurzů...');

        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        foreach (Course::where('status', 'active')->get() as $course) {
            $result = $provider->sync($course);

            match (true) {
                $result->succeeded => $results['success']++,
                $result->skipped => $results['skipped']++,
                default => $results['failed']++,
            };
        }

        $this->info('Synchronizace dokončena:');
        $this->table(
            ['Stav', 'Počet'],
            [
                ['✅ Úspěšně synchronizováno', $results['success']],
                ['❌ Chyba při synchronizaci', $results['failed']],
                ['⏭️  Přeskočeno (bez externího ID)', $results['skipped']],
            ]
        );

        return self::SUCCESS;
    }
}
