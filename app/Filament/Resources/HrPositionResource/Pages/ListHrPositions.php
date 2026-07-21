<?php

namespace App\Filament\Resources\HrPositionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHrPositions extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\HrPositionResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
