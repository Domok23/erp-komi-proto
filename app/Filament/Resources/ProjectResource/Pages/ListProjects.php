<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProjects extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\ProjectResource';

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
