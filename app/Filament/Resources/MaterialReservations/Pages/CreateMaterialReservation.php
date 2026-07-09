<?php

namespace App\Filament\Resources\MaterialReservations\Pages;

use App\Filament\Resources\MaterialReservations\MaterialReservationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMaterialReservation extends CreateRecord
{
    protected static string $resource = MaterialReservationResource::class;
}
