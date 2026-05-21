<?php

namespace App\Filament\Resources\CostingResource\Pages;

use App\Filament\Resources\CostingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCostings extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\CostingResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}