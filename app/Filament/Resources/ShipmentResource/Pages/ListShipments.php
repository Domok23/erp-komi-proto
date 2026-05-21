<?php
namespace App\Filament\Resources\ShipmentResource\Pages;
use App\Filament\Resources\ShipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShipments extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\ShipmentResource';
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
