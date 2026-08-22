<?php

namespace App\Filament\Resources\GalleryResource\Pages;

use App\Filament\Resources\GalleryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGallery extends EditRecord
{
    protected static string $resource = GalleryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    /**
     * Popisky fotek doplňuje až Gallery::booted() při ukládání, takže po
     * uložení nesedí to, co drží formulář, s tím, co je v databázi. Bez
     * přenačtení by redaktor koukal na prázdná pole a myslel si, že
     * číslování nefunguje — přitom stačí obnovit stránku.
     *
     * CreateGallery tohle nepotřebuje: nemáme stránku 'view', takže
     * CreateRecord::getRedirectUrl() přesměruje na edit a formulář se
     * postaví z čerstvého záznamu sám.
     */
    protected function afterSave(): void
    {
        $this->refreshFormData(['photos']);
    }
}
