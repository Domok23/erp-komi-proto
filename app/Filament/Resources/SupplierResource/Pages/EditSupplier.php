<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Resources\SupplierResource;
use App\Models\Supplier;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSupplier extends EditRecord
{
    protected static string $resource = SupplierResource::class;

    public function getRelationManagers(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (Actions\DeleteAction $action) {
                    /** @var Supplier $record */
                    $record = $this->getRecord();
                    $blockers = $record->getDeletionBlockers();
                    if (! empty($blockers)) {
                        Notification::make()
                            ->title('Cannot Delete Supplier')
                            ->body("Supplier '{$record->name}' [{$record->code}] cannot be deleted because it is linked to: ".implode(', ', $blockers).'. Please remove or reassign them first.')
                            ->danger()
                            ->persistent()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
