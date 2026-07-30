<?php

namespace App\Filament\Resources\CourseTypeResource\Pages;

use App\Filament\Resources\CourseTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCourseTypes extends ListRecords
{
    protected static string $resource = CourseTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
