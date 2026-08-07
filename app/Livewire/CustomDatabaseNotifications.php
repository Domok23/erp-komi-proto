<?php

namespace App\Livewire;

use App\Filament\Resources\NotificationLogResource;
use Filament\Actions\Action;
use Filament\Livewire\DatabaseNotifications as BaseDatabaseNotifications;
use Illuminate\Contracts\View\View;

class CustomDatabaseNotifications extends BaseDatabaseNotifications
{
    public function viewAlertHistoryAction(): Action
    {
        return Action::make('viewAlertHistory')
            ->link()
            ->color('gray')
            ->icon('heroicon-m-clock')
            ->label('History')
            ->url(fn (): string => NotificationLogResource::getUrl('index'));
    }

    public function render(): View
    {
        return view('livewire.custom-database-notifications');
    }
}
