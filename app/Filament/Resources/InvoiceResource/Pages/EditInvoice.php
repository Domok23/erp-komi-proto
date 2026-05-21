<?php
namespace App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\InvoiceResource';
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
