<?php

namespace App\Filament\Resources\HrLeaveTypeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHrLeaveTypes extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\HrLeaveTypeResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
