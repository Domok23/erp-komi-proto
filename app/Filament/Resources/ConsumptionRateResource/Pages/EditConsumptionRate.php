<?php

namespace App\Filament\Resources\ConsumptionRateResource\Pages;

use App\Filament\Resources\ConsumptionRateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConsumptionRate extends EditRecord
{
    protected static string $resource = ConsumptionRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
