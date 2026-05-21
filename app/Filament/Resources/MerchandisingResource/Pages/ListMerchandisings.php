<?php
namespace App\Filament\Resources\MerchandisingResource\Pages;
use App\Filament\Resources\MerchandisingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMerchandisings extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\MerchandisingResource';
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
