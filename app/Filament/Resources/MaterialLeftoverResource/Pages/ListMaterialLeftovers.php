<?php

namespace App\Filament\Resources\MaterialLeftoverResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaterialLeftovers extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\MaterialLeftoverResource';

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
