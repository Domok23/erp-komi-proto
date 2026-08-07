<?php

namespace App\Filament\Resources\PoSupplierResource\Pages;

use App\Filament\Resources\PoSupplierResource;
use App\Models\PoSupplier;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\HtmlString;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

class EditPoSupplier extends EditRecord
{
    protected static string $resource = PoSupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PoSupplierResource::getSubmitForApprovalAction(),
            PoSupplierResource::getApproveSignAction(),
            PoSupplierResource::getRejectApprovalAction(),
            PoSupplierResource::getRevisePoAction(),
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
