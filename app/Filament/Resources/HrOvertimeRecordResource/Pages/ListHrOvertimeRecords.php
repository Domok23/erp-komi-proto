<?php

namespace App\Filament\Resources\HrOvertimeRecordResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHrOvertimeRecords extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\HrOvertimeRecordResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
