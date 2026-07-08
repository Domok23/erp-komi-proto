<?php

namespace App\Filament\Resources\MaterialReservations\Pages;

use App\Filament\Resources\MaterialReservations\MaterialReservationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMaterialReservations extends ListRecords
{
    protected static string $resource = MaterialReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
