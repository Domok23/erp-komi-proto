<?php

namespace App\Filament\Resources\WarehouseResource\Pages;

use App\Filament\Resources\WarehouseResource;
use App\Models\Warehouse;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditWarehouse extends EditRecord
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (Actions\DeleteAction $action) {
                    /** @var Warehouse $record */
                    $record = $this->getRecord();
                    $blockers = $record->getDeletionBlockers();
                    if (! empty($blockers)) {
                        Notification::make()
                            ->title('Cannot Delete Warehouse')
                            ->body("Warehouse '{$record->name}' [{$record->code}] cannot be deleted because it is linked to: ".implode(', ', $blockers).'. Please transfer stock or reassign them first.')
                            ->danger()
                            ->persistent()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
