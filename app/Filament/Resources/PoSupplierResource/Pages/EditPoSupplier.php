<?php

namespace App\Filament\Resources\PoSupplierResource\Pages;

use App\Filament\Actions\StockPreviewAction;
use App\Filament\Resources\PoSupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPoSupplier extends EditRecord
{
    protected static string $resource = PoSupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            StockPreviewAction::make('form'),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->record->load('items');
        $this->record->recalculateTotals();
        $this->record->syncStatusFromItems();
    }
}
