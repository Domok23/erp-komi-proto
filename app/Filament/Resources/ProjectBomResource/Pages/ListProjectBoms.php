<?php
namespace App\Filament\Resources\ProjectBomResource\Pages;
use App\Filament\Resources\ProjectBomResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProjectBoms extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\ProjectBomResource';
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
