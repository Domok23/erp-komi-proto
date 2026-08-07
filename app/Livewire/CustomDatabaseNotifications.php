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
            ->button()
            ->size('xs')
            ->color('gray')
            ->label('History')
            ->url(fn (): string => NotificationLogResource::getUrl('index'));
    }

    public function markAllNotificationsAsReadAction(): Action
    {
        return Action::make('markAllNotificationsAsRead')
            ->button()
            ->size('xs')
            ->color('primary')
            ->label(__('filament-notifications::database.modal.actions.mark_all_as_read.label'))
            ->extraAttributes(['tabindex' => '-1'])
            ->action('markAllNotificationsAsRead');
    }

    public function clearNotificationsAction(): Action
    {
        return Action::make('clearNotifications')
            ->button()
            ->size('xs')
            ->color('danger')
            ->label('Clear all')
            ->extraAttributes(['tabindex' => '-1'])
            ->action('clearNotifications')
            ->close();
    }

    public function render(): View
    {
        return view('livewire.custom-database-notifications');
    }
}
