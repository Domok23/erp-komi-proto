<?php

namespace App\Filament\Resources\ConsumptionRateResource\Pages;

use App\Filament\Resources\ConsumptionRateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConsumptionRates extends ListRecords
{
    protected static string $resource = ConsumptionRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
