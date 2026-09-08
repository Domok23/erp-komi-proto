<?php

namespace App\Filament\Resources\PoSupplierResource\Pages;

use App\Filament\Resources\PoSupplierResource;
use App\Models\PoSupplier;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPoSupplier extends EditRecord
{
    protected static string $resource = PoSupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PoSupplierResource::getSubmitForApprovalAction(),
            PoSupplierResource::getApproveSignAction(),
            PoSupplierResource::getRejectApprovalAction(),
            PoSupplierResource::getRevisePoAction(),
            Actions\DeleteAction::make()
                ->before(function (Actions\DeleteAction $action) {
                    /** @var PoSupplier $record */
                    $record = $this->getRecord();
                    $blockers = $record->getDeletionBlockers();
                    if (! empty($blockers)) {
                        Notification::make()
                            ->title('Cannot Delete Purchase Order')
                            ->body("Purchase Order '{$record->po_number}' cannot be deleted because: ".implode(', ', $blockers).'. Please resolve them first.')
                            ->danger()
                            ->persistent()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }

    protected function afterSave(): void
    {
        $this->record->load('items');
        $this->record->recalculateTotals();
        $this->record->syncStatusFromItems();
    }
}
