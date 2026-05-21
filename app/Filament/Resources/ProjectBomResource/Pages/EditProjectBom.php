<?php
namespace App\Filament\Resources\ProjectBomResource\Pages;
use App\Filament\Resources\ProjectBomResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProjectBom extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\ProjectBomResource';
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
