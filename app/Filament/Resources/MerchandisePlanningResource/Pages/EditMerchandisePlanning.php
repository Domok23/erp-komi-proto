<?php

namespace App\Filament\Resources\MerchandisePlanningResource\Pages;

use App\Filament\Resources\MerchandisePlanningResource;
use App\Models\MerchandisePlanning;
use App\Services\MerchandisePlanningSyncService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditMerchandisePlanning extends EditRecord
{
    protected static string $resource = MerchandisePlanningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('resyncFromDesign')
                ->label('Re-sync from R&D Design')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (MerchandisePlanning $record) => in_array($record->status, ['preliminary', 'tech_pack', 'draft']))
                ->requiresConfirmation()
                ->modalHeading('Re-sync Materials from R&D Design')
                ->modalDescription('This will refresh planned quantities and default prices from the current R&D Consumption Rates while preserving any assigned suppliers, subcons, and custom edits.')
                ->action(function (MerchandisePlanning $record) {
                    $count = MerchandisePlanningSyncService::syncFromDesign($record);
                    Notification::make()
                        ->title('Synchronized from R&D')
                        ->body("{$count} material items refreshed from R&D Consumption Rates.")
                        ->success()
                        ->send();

                    $this->refreshFormData(['items', 'total_material_cost', 'total_subcon_cost']);
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
