<?php

namespace App\Filament\Resources\HrAttendanceResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHrAttendances extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\HrAttendanceResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
