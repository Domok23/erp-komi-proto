<?php

namespace App\Filament\Resources\MaterialResource\Pages;

use App\Models\Material;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditMaterial extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\MaterialResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (Actions\DeleteAction $action) {
                    /** @var Material $record */
                    $record = $this->getRecord();
                    $blockers = $record->getDeletionBlockers();
                    if (! empty($blockers)) {
                        Notification::make()
                            ->title('Cannot Delete Material')
                            ->body("Material '{$record->name}' [{$record->code}] cannot be deleted because it is linked to: ".implode(', ', $blockers).'. Please adjust stock or relations first.')
                            ->danger()
                            ->persistent()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
