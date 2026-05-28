<?php

namespace App\Filament\Resources\PoSubconResource\Pages;

use App\Filament\Resources\PoSubconResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPoSubcons extends ListRecords
{
    protected static string $resource = PoSubconResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
