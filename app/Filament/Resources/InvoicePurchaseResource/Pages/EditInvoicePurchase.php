<?php

namespace App\Filament\Resources\InvoicePurchaseResource\Pages;

use App\Filament\Resources\InvoicePurchaseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvoicePurchase extends EditRecord
{
    protected static string $resource = InvoicePurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
