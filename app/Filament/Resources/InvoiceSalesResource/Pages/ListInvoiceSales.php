<?php

namespace App\Filament\Resources\InvoiceSalesResource\Pages;

use App\Filament\Resources\InvoiceSalesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInvoiceSales extends ListRecords
{
    protected static string $resource = InvoiceSalesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
