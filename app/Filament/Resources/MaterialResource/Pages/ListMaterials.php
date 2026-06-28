<?php

namespace App\Filament\Resources\MaterialResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaterials extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\MaterialResource';

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
