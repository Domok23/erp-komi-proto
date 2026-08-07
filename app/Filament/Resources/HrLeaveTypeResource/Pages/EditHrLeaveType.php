<?php

namespace App\Filament\Resources\HrLeaveTypeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHrLeaveType extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\HrLeaveTypeResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
