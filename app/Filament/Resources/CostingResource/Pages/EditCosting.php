<?php

namespace App\Filament\Resources\CostingResource\Pages;

use App\Filament\Resources\CostingResource;
use App\Models\Costing;
use App\Services\CostingCalculatorService;
use App\Services\CostingTransitionService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCosting extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\CostingResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('calculate')
                ->label('Calculate')
                ->icon('heroicon-o-calculator')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn (Costing $record): bool => $record->status === 'draft')
                ->action(function (Costing $record) {
                    // Auto-import BOM material cost if empty
                    if ($record->material_cost <= 0 && $record->project) {
                        $bomResult = CostingCalculatorService::calculateFromBOM($record->project);
                        if ($bomResult['material_cost'] > 0) {
                            $record->update(['material_cost' => $bomResult['material_cost']]);
                        }
                    }

                    // Auto-fill MP cost from config if empty
                    if ($record->mp_cost <= 0 && $record->project) {
                        $mpCost = CostingCalculatorService::getMpRatePerUnit();
                        $record->update(['mp_cost' => $mpCost]);
                    }

                    $record->refresh();

                    // Final check: still no costs?
                    if ($record->material_cost <= 0 && $record->mp_cost <= 0) {
                        Notification::make()
                            ->title('Cannot calculate: project has no BOM and MP cost is 0')
                            ->danger()
                            ->send();

                        return;
                    }

                    CostingCalculatorService::recalculateCosting($record);
                    $record->refresh();
                    $record->update(['status' => 'calculated']);

                    $this->refreshFormData(['status', 'material_cost', 'mp_cost']);

                    Notification::make()
                        ->title('Calculated — Material: IDR '.number_format($record->material_cost, 2, '.', ',')
                            .', MP: IDR '.number_format($record->mp_cost, 2, '.', ',')
                            .', Selling: IDR '.number_format($record->selling_price, 2, '.', ','))
                        ->success()
                        ->send();
                }),

            Actions\Action::make('submit')
                ->label('Submit for Approval')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (Costing $record): bool => $record->canBeSubmitted())
                ->action(function (Costing $record) {
                    CostingTransitionService::submit($record);
                    $this->refreshFormData(['status', 'submitted_by', 'submitted_at']);

                    Notification::make()
                        ->title('Submitted for approval')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('unsubmit')
                ->label('Kembalikan ke Draft')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (Costing $record): bool => $record->canBeUnsubmitted())
                ->action(function (Costing $record) {
                    $record->transitionTo('draft');
                    $this->refreshFormData(['status']);

                    Notification::make()
                        ->title('Returned to Draft')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (Costing $record): bool => $record->canBeApproved())
                ->action(function (Costing $record) {
                    CostingTransitionService::approve($record);
                    $this->refreshFormData(['status']);

                    Notification::make()
                        ->title('Costing Approved — Price Locked')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (Costing $record): bool => $record->canBeRejected())
                ->action(function (Costing $record) {
                    CostingTransitionService::reject($record);
                    $this->refreshFormData(['status']);

                    Notification::make()
                        ->title('Costing Rejected')
                        ->danger()
                        ->send();
                }),

            Actions\Action::make('newVersion')
                ->label('Buat Versi Baru')
                ->icon('heroicon-o-document-plus')
                ->color('info')
                ->visible(fn (Costing $record): bool => $record->canCreateNewVersion())
                ->action(function (Costing $record) {
                    $new = CostingTransitionService::createNewVersion($record);

                    Notification::make()
                        ->title("Versi baru v{$new->version} dibuat")
                        ->success()
                        ->send();

                    $this->redirect(CostingResource::getUrl('edit', ['record' => $new->id]));
                }),

            Actions\Action::make('duplicate')
                ->label('Duplicate')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->visible(fn (Costing $record): bool => $record->canBeDuplicated())
                ->action(function (Costing $record) {
                    $new = CostingTransitionService::duplicate($record);

                    Notification::make()
                        ->title("Duplicated to v{$new->version}")
                        ->success()
                        ->send();

                    $this->redirect(CostingResource::getUrl('edit', ['record' => $new->id]));
                }),

            Actions\DeleteAction::make()
                ->visible(fn (Costing $record): bool => $record->status === 'draft'),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->record;

        // Fix #7: If user edits while submitted, reset to draft
        if ($record->status === 'submitted') {
            $record->update(['status' => 'draft']);
            $this->refreshFormData(['status']);

            Notification::make()
                ->title('Data changed — status reset to Draft. Please re-submit.')
                ->warning()
                ->send();

            return;
        }

        // Fix #3: Only auto-calculate if ALL required costs are filled
        if ($record->status === 'draft' && $record->hasAllCostsFilled()) {
            $record->update(['status' => 'calculated']);
            $this->refreshFormData(['status']);
        }
    }
}
