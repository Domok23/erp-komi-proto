<?php

namespace App\Filament\Resources\GoodsReceiptResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGoodsReceipt extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\GoodsReceiptResource';

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
