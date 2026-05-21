<?php
namespace App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\ProductResource';
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
