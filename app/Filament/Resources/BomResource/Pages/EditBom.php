<?php

namespace App\Filament\Resources\BomResource\Pages;

use App\Filament\Actions\StockPreviewAction;
use App\Filament\Resources\BomResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBom extends EditRecord
{
    protected static string $resource = BomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            StockPreviewAction::make('form'),
            Actions\DeleteAction::make(),
        ];
    }
}
