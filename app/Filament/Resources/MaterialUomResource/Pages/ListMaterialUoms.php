<?php

namespace App\Filament\Resources\MaterialUomResource\Pages;

use App\Filament\Resources\MaterialUomResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaterialUoms extends ListRecords
{
    protected static string $resource = MaterialUomResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
