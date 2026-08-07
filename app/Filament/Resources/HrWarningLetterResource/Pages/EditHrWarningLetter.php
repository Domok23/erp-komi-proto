<?php

namespace App\Filament\Resources\HrWarningLetterResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHrWarningLetter extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\HrWarningLetterResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
