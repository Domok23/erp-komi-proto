<?php

namespace App\Filament\Resources\HrLeaveRequestResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHrLeaveRequests extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\HrLeaveRequestResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
