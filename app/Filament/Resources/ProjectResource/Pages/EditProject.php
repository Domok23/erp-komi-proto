<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\ProjectResource';

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
