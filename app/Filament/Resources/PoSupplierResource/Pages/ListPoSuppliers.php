<?php

namespace App\Filament\Resources\PoSupplierResource\Pages;

use App\Filament\Resources\PoSupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPoSuppliers extends ListRecords
{
    protected static string $resource = PoSupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
