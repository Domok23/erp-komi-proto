<?php

namespace App\Filament\Resources\HrLeaveRequestResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHrLeaveRequests extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\HrLeaveRequestResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('leave_types')
                ->label('Leave Types')
                ->icon('heroicon-o-calendar-days')
                ->color('secondary')
                ->modalHeading('Manage Leave Types')
                ->modalWidth('5xl')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->modalContent(fn () => view('filament.pages.manage-leave-types-modal-wrapper')),
            Actions\CreateAction::make(),
        ];
    }
}
