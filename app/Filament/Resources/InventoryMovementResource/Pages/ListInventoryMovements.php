<?php

namespace App\Filament\Resources\InventoryMovementResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventoryMovements extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\InventoryMovementResource';

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
