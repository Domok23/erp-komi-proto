<?php

namespace App\Filament\Resources\HrLeaveRequestResource\Pages;

use App\Models\Company;
use App\Services\CompanyContext;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListHrLeaveRequests extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\HrLeaveRequestResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('self_service')
                ->label('Self Service Leave')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(function (): ?string {
                    $company = CompanyContext::getCompany() ?? Auth::user()?->company ?? Company::first();

                    return $company ? route('leave-request.lookup', $company->code) : null;
                })
                ->openUrlInNewTab()
                ->visible(function (): bool {
                    $company = CompanyContext::getCompany() ?? Auth::user()?->company ?? Company::first();

                    return $company !== null;
                }),
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
