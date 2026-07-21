<?php

namespace App\Filament\Resources\HrEmployeeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHrEmployees extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\HrEmployeeResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
