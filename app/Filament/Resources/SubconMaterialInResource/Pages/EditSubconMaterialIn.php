<?php

namespace App\Filament\Resources\SubconMaterialInResource\Pages;

use App\Filament\Resources\SubconMaterialInResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubconMaterialIn extends EditRecord
{
    protected static string $resource = SubconMaterialInResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
