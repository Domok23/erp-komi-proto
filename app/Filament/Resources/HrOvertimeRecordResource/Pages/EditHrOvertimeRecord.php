<?php

namespace App\Filament\Resources\HrOvertimeRecordResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHrOvertimeRecord extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\HrOvertimeRecordResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
