<?php

namespace App\Filament\Resources\HrEmployeeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHrEmployees extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\HrEmployeeResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('candidates')
                ->label('Candidates')
                ->icon('heroicon-o-user-plus')
                ->color('secondary')
                ->modalHeading('Manage Candidates')
                ->modalWidth('5xl')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->modalContent(fn () => view('filament.pages.manage-candidates-modal-wrapper')),
            Actions\Action::make('warning_letters')
                ->label('Warning Letters')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('warning')
                ->modalHeading('Manage Warning Letters')
                ->modalWidth('5xl')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->modalContent(fn () => view('filament.pages.manage-warning-letters-modal-wrapper')),
            Actions\CreateAction::make(),
        ];
    }
}
