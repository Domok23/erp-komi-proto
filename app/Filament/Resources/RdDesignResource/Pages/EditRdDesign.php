<?php
namespace App\Filament\Resources\RdDesignResource\Pages;
use App\Filament\Resources\RdDesignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRdDesign extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\RdDesignResource';
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
