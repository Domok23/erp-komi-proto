<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShipment extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\ShipmentResource';

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
