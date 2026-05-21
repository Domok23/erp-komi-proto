<?php
namespace App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\ProjectResource';
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
