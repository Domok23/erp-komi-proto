<?php
namespace App\Filament\Resources\MaterialLeftoverResource\Pages;
use App\Filament\Resources\MaterialLeftoverResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMaterialLeftover extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\MaterialLeftoverResource';
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
