<?php

namespace App\Filament\Resources\HrDepartmentResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHrDepartment extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\HrDepartmentResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
