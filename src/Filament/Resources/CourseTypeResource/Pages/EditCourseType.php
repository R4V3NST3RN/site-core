<?php

namespace App\Filament\Resources\CourseTypeResource\Pages;

use App\Filament\Resources\CourseTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCourseType extends EditRecord
{
    protected static string $resource = CourseTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
