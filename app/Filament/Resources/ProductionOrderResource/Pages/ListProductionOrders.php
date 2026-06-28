<?php
namespace App\Filament\Resources\ProductionOrderResource\Pages;
use App\Filament\Resources\ProductionOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductionOrders extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\ProductionOrderResource';
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
