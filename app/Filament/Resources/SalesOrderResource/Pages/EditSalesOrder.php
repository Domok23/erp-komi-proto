<?php

namespace App\Filament\Resources\SalesOrderResource\Pages;

use App\Models\SalesOrder;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSalesOrder extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\SalesOrderResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (Actions\DeleteAction $action) {
                    /** @var SalesOrder $record */
                    $record = $this->getRecord();
                    $blockers = $record->getDeletionBlockers();
                    if (! empty($blockers)) {
                        Notification::make()
                            ->title('Cannot Delete Sales Order')
                            ->body("Sales Order '{$record->so_number}' cannot be deleted because: ".implode(', ', $blockers).'. Please resolve them first.')
                            ->danger()
                            ->persistent()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
