<?php

namespace App\Filament\Resources\PoSubconResource\Pages;

use App\Filament\Resources\PoSubconResource;
use App\Models\PoSubcon;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPoSubcon extends EditRecord
{
    protected static string $resource = PoSubconResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (Actions\DeleteAction $action) {
                    /** @var PoSubcon $record */
                    $record = $this->getRecord();
                    $blockers = $record->getDeletionBlockers();
                    if (! empty($blockers)) {
                        Notification::make()
                            ->title('Cannot Delete Subcon PO')
                            ->body("Subcon PO '{$record->po_number}' cannot be deleted because: ".implode(', ', $blockers).'. Please resolve them first.')
                            ->danger()
                            ->persistent()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
