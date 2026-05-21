<?php
namespace App\Filament\Resources\RdDesignResource\Pages;
use App\Filament\Resources\RdDesignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRdDesigns extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\RdDesignResource';
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
