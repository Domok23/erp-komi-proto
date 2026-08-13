<?php

namespace App\Filament\Resources\InventoryMovementResource\Pages;

use Filament\Resources\Pages\CreateRecord;

class CreateInventoryMovement extends CreateRecord
{
    protected static string $resource = 'App\Filament\Resources\InventoryMovementResource';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($data['type'] === 'adjustment') {
            $qty = abs(floatval($data['quantity']));
            $data['quantity'] = ($data['direction'] ?? 'addition') === 'subtraction' ? -$qty : $qty;
        }
        unset($data['direction']);

        return $data;
    }
}
