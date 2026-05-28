<?php

namespace App\Filament\Resources\PoSubconResource\Pages;

use App\Filament\Resources\PoSubconResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPoSubcon extends EditRecord
{
    protected static string $resource = PoSubconResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
