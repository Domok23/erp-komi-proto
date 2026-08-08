<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Exceptions\ProjectArchiveException;
use App\Services\ProjectArchiveService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class EditProject extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\ProjectResource';

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if ($this->getRecord()->isArchived()) {
            $this->form->disabled();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('restore')
                ->label('Restore')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->visible(fn () => $this->getRecord()->isArchived() && Auth::user()?->isAdmin())
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        ProjectArchiveService::restore($this->getRecord(), Auth::user());
                        Notification::make()->title('Project restored')->success()->send();
                        $this->refreshFormData(['archived_at', 'archived_by']);
                    } catch (ProjectArchiveException $e) {
                        Notification::make()->title('Restore failed')->body($e->getMessage())->danger()->send();
                    }
                }),
            Actions\DeleteAction::make()
                ->visible(fn () => ! $this->getRecord()->isArchived()),
        ];
    }

    protected function getFormActions(): array
    {
        if ($this->getRecord()->isArchived()) {
            return [];
        }

        return parent::getFormActions();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->getRecord()->isArchived()) {
            $this->halt();
        }

        return $data;
    }

    public function getTitle(): string|Htmlable
    {
        $record = $this->getRecord();
        if ($record->isArchived()) {
            $by = $record->archivedByUser?->name ?? 'unknown';
            $at = $record->archived_at?->format('Y-m-d H:i') ?? '';

            return "{$record->name} (Archived {$at} by {$by})";
        }

        return parent::getTitle();
    }
}
