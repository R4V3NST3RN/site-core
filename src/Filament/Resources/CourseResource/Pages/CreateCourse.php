<?php

namespace App\Filament\Resources\CourseResource\Pages;

use App\Filament\Resources\CourseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCourse extends CreateRecord
{
    protected static string $resource = CourseResource::class;

    /**
     * Doplní ID nově vytvořeného kurzu do bloků typu course_info.
     *
     * V CourseResource je course_id skryté pole, které se plní z
     * editovaného záznamu — jenže při zakládání kurzu záznam ještě
     * neexistuje a blok by se uložil s course_id = null. Renderer
     * takový blok tiše přeskočí, takže by klient, který si blok
     * "Info o kurzu" vloží rovnou při zakládání, dostal na webu
     * prázdno bez jakéhokoli varování.
     *
     * Hook běží uvnitř transakce vytvoření, kdy už $this->record má
     * ID. Záchranná síť v ContentBlocks (afterStateHydrated) zůstává
     * pro záznamy, které vznikly dřív a null v sobě už mají.
     */
    protected function afterCreate(): void
    {
        $blocks = $this->record->blocks;

        if (blank($blocks)) {
            return;
        }

        $changed = false;

        foreach ($blocks as $index => $block) {
            if (($block['type'] ?? null) !== 'course_info') {
                continue;
            }

            if (filled($block['data']['course_id'] ?? null)) {
                continue;
            }

            $blocks[$index]['data']['course_id'] = $this->record->getKey();
            $changed = true;
        }

        if (! $changed) {
            return;
        }

        $this->record->blocks = $blocks;

        // saveQuietly: jde o dopočet uvnitř zakládání, ne o samostatnou
        // uživatelskou editaci — 'updated' event by tu byl matoucí.
        $this->record->saveQuietly();
    }
}
