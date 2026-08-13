<?php

namespace App\Filament\Resources\MaterialUsageResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaterialUsages extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\MaterialUsageResource';

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
