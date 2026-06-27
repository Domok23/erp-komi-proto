<?php

namespace App\Filament\Resources\SalesOrderResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalesOrder extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\SalesOrderResource';

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
