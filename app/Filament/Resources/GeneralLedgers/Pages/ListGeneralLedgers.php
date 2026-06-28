<?php

namespace App\Filament\Resources\GeneralLedgers\Pages;

use App\Filament\Resources\GeneralLedgers\GeneralLedgerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGeneralLedgers extends ListRecords
{
    protected static string $resource = GeneralLedgerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
