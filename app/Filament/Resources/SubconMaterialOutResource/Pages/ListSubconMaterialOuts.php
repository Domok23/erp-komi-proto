<?php

namespace App\Filament\Resources\SubconMaterialOutResource\Pages;

use App\Filament\Resources\SubconMaterialOutResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubconMaterialOuts extends ListRecords
{
    protected static string $resource = SubconMaterialOutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
