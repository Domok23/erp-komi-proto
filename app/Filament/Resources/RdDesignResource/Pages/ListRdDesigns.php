<?php

namespace App\Filament\Resources\RdDesignResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRdDesigns extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\RdDesignResource';

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
