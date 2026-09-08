<?php

namespace App\Filament\Resources\SubconResource\Pages;

use App\Models\Subcon;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSubcon extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\SubconResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (Actions\DeleteAction $action) {
                    /** @var Subcon $record */
                    $record = $this->getRecord();
                    $blockers = $record->getDeletionBlockers();
                    if (! empty($blockers)) {
                        Notification::make()
                            ->title('Cannot Delete Subcontractor')
                            ->body("Subcontractor '{$record->name}' [{$record->code}] cannot be deleted because it is linked to: ".implode(', ', $blockers).'. Please remove or reassign them first.')
                            ->danger()
                            ->persistent()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
