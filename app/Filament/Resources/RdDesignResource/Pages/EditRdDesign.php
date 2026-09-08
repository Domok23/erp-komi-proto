<?php

namespace App\Filament\Resources\RdDesignResource\Pages;

use App\Models\RdDesign;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditRdDesign extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\RdDesignResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('createRevision')
                ->label('New Revision')
                ->icon('heroicon-o-document-duplicate')
                ->color('warning')
                ->form([
                    Forms\Components\TextInput::make('new_version')
                        ->label('New Version Number')
                        ->default(fn () => sprintf('%.1f', ((float) ($this->getRecord()->version ?: '1.0')) + 0.1))
                        ->required(),
                ])
                ->action(function (array $data) {
                    /** @var RdDesign $record */
                    $record = $this->getRecord();
                    $revision = $record->createRevision($data['new_version']);

                    Notification::make()
                        ->title('Revision Created')
                        ->body("Created revision v{$revision->version} for {$record->name}")
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $revision]));
                }),
            Actions\Action::make('approveDesign')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->getRecord()->status !== 'approved')
                ->requiresConfirmation()
                ->action(function () {
                    /** @var RdDesign $record */
                    $record = $this->getRecord();
                    $record->update(['status' => 'approved']);

                    Notification::make()
                        ->title('Design Approved')
                        ->body("{$record->name} (v{$record->version}) has been approved.")
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),
            Actions\DeleteAction::make()
                ->before(function (Actions\DeleteAction $action) {
                    /** @var RdDesign $record */
                    $record = $this->getRecord();
                    $blockers = $record->getDeletionBlockers();
                    if (! empty($blockers)) {
                        Notification::make()
                            ->title('Cannot Delete Design')
                            ->body("Design '{$record->name}' [{$record->code}] cannot be deleted because it is linked to: ".implode(', ', $blockers).'. Please reassign or archive first.')
                            ->danger()
                            ->persistent()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
