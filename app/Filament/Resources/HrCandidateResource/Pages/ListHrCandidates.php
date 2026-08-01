<?php

namespace App\Filament\Resources\HrCandidateResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHrCandidates extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\HrCandidateResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', '!=', 'hired')->count();
    }
}
