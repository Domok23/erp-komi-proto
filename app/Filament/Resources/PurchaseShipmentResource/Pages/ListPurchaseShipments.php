<?php

namespace App\Filament\Resources\PurchaseShipmentResource\Pages;

use App\Filament\Resources\PurchaseShipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseShipments extends ListRecords
{
    protected static string $resource = PurchaseShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
