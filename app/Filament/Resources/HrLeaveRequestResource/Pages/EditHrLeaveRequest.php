<?php

namespace App\Filament\Resources\HrLeaveRequestResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHrLeaveRequest extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\HrLeaveRequestResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
