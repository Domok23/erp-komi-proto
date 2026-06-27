<?php

namespace App\Filament\Resources\SubconResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubcons extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\SubconResource';

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
