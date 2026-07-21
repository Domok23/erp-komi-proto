<?php

namespace App\Filament\Resources\HrDepartmentResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHrDepartments extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\HrDepartmentResource';

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
