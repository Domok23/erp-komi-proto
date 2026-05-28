<?php

namespace App\Filament\Resources\InvoiceSalesResource\Pages;

use App\Filament\Resources\InvoiceSalesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvoiceSales extends EditRecord
{
    protected static string $resource = InvoiceSalesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
