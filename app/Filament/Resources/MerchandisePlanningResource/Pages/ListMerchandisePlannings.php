<?php

namespace App\Filament\Resources\MerchandisePlanningResource\Pages;

use App\Filament\Resources\MerchandisePlanningResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMerchandisePlannings extends ListRecords
{
    protected static string $resource = MerchandisePlanningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
