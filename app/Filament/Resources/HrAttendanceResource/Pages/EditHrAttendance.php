<?php

namespace App\Filament\Resources\HrAttendanceResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHrAttendance extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\HrAttendanceResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
