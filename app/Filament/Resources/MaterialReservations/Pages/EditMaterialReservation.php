<?php

namespace App\Filament\Resources\MaterialReservations\Pages;

use App\Filament\Resources\MaterialReservations\MaterialReservationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMaterialReservation extends EditRecord
{
    protected static string $resource = MaterialReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
