<?php

namespace App\Filament\Resources\JobOrderResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJobOrders extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\JobOrderResource';

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
