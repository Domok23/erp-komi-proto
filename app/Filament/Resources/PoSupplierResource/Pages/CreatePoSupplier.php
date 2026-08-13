<?php

namespace App\Filament\Resources\PoSupplierResource\Pages;

use App\Filament\Resources\PoSupplierResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePoSupplier extends CreateRecord
{
    protected static string $resource = PoSupplierResource::class;

    protected function afterSave(): void
    {
        $this->record->load('items');
        $this->record->recalculateTotals();
        $this->record->syncStatusFromItems();
    }
}
