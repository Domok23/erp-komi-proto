<?php

namespace App\Filament\Resources\GeneralLedgers\Pages;

use App\Filament\Resources\GeneralLedgers\GeneralLedgerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGeneralLedger extends EditRecord
{
    protected static string $resource = GeneralLedgerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
