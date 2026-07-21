<?php

namespace App\Filament\Resources\HrPositionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHrPosition extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\HrPositionResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
