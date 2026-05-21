<?php
namespace App\Filament\Resources\SubconResource\Pages;
use App\Filament\Resources\SubconResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubcon extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\SubconResource';
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
