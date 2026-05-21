<?php
namespace App\Filament\Resources\MerchandisingResource\Pages;
use App\Filament\Resources\MerchandisingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMerchandising extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\MerchandisingResource';
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
