<?php

namespace App\Filament\Resources\SubconMaterialOutResource\Pages;

use App\Filament\Actions\StockPreviewAction;
use App\Filament\Resources\SubconMaterialOutResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubconMaterialOut extends EditRecord
{
    protected static string $resource = SubconMaterialOutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            StockPreviewAction::make('form'),
            Actions\DeleteAction::make(),
        ];
    }
}
