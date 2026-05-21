<?php
namespace App\Filament\Resources\GoodsReceiptResource\Pages;
use App\Filament\Resources\GoodsReceiptResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGoodsReceipts extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\GoodsReceiptResource';
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
