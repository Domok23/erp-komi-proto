<?php

namespace App\Filament\Resources\QcInspectionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQcInspection extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\QcInspectionResource';

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
