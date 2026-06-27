<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupplier extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\SupplierResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
