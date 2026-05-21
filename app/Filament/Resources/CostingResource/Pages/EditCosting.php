<?php

namespace App\Filament\Resources\CostingResource\Pages;

use App\Filament\Resources\CostingResource;
use Filament\Resources\Pages\EditRecord;

class EditCosting extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\CostingResource';

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}