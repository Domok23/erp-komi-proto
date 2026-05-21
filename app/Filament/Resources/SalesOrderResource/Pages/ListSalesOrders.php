<?php
namespace App\Filament\Resources\SalesOrderResource\Pages;
use App\Filament\Resources\SalesOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalesOrders extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\SalesOrderResource';
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
