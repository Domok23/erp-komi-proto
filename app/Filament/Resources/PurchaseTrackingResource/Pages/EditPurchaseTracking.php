<?php

namespace App\Filament\Resources\PurchaseTrackingResource\Pages;

use App\Filament\Resources\PurchaseTrackingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseTracking extends EditRecord
{
    protected static string $resource = PurchaseTrackingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
