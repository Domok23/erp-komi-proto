<?php

namespace App\Filament\Resources\MerchandisePlanningResource\Pages;

use App\Filament\Actions\StockPreviewAction;
use App\Filament\Resources\MerchandisePlanningResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMerchandisePlanning extends EditRecord
{
    protected static string $resource = MerchandisePlanningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            StockPreviewAction::make('form'),
            Actions\DeleteAction::make(),
        ];
    }
}
