<?php

namespace App\Filament\Resources\InventoryMovementResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInventoryMovement extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\InventoryMovementResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->disabled(fn ($record) => $record && $record->reference_type !== null),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (($data['type'] ?? null) === 'adjustment') {
            $qty = floatval($data['quantity'] ?? 0);
            $data['direction'] = $qty < 0 ? 'subtraction' : 'addition';
            $data['quantity'] = abs($qty);
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['type'] === 'adjustment') {
            $qty = abs(floatval($data['quantity']));
            $data['quantity'] = ($data['direction'] ?? 'addition') === 'subtraction' ? -$qty : $qty;
        }
        unset($data['direction']);

        return $data;
    }
}
