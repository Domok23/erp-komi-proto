<?php
namespace App\Filament\Resources\InventoryResource\Pages;
use App\Filament\Resources\InventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInventory extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\InventoryResource';
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
