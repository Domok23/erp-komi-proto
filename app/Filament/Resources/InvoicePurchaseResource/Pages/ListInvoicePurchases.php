<?php

namespace App\Filament\Resources\InvoicePurchaseResource\Pages;

use App\Filament\Resources\InvoicePurchaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInvoicePurchases extends ListRecords
{
    protected static string $resource = InvoicePurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
