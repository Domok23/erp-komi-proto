<?php

namespace App\Filament\Resources\PoSupplierResource\Pages;

use App\Filament\Resources\PoSupplierResource;
use App\Models\PoSupplier;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\HtmlString;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

class EditPoSupplier extends EditRecord
{
    protected static string $resource = PoSupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Submit for Approval action
            Actions\Action::make('submit_for_approval')
                ->label('Submit for Approval')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(fn (PoSupplier $record) => $record->approval_status === 'draft' && $record->items()->count() > 0)
                ->action(function (PoSupplier $record) {
                    $record->update([
                        'approval_status' => 'pending_approval',
                    ]);

                    // Level 1: manager is always required
                    $record->approvals()->create([
                        'approval_level' => 'manager',
                        'status' => 'pending',
                    ]);

                    // Level 2: director is required if grand_total >= 100M IDR
                    if (floatval($record->grand_total) >= 100000000) {
                        $record->approvals()->create([
                            'approval_level' => 'director',
                            'status' => 'pending',
                        ]);
                    }

                    Notification::make()
                        ->title('PO submitted for authorization!')
                        ->success()
                        ->send();
                    
                    return redirect()->to(PoSupplierResource::getUrl('edit', ['record' => $record]));
                })
                ->requiresConfirmation(),

            // Approve & Sign action
            Actions\Action::make('approve_sign')
                ->label('Approve & Sign')
                ->icon('heroicon-o-pencil-square')
                ->color('success')
                ->visible(function (PoSupplier $record) {
                    if ($record->approval_status !== 'pending_approval') {
                        return false;
                    }
                    
                    // Find active level
                    $activeLevel = $record->approvals()->where('status', 'pending')->orderBy('id', 'asc')->first();
                    if (!$activeLevel) {
                        return false;
                    }

                    // Level 2 (director) can only be signed if Level 1 (manager) is approved
                    if ($activeLevel->approval_level === 'director') {
                        $managerApproved = $record->approvals()->where('approval_level', 'manager')->where('status', 'approved')->exists();
                        if (!$managerApproved) {
                            return false;
                        }
                    }

                    return true;
                })
                ->form(function (PoSupplier $record) {
                    $formFields = [];
                    $user = auth()->user();
                    
                    if ($user && $user->signature) {
                        $formFields[] = Forms\Components\Placeholder::make('saved_signature_preview')
                            ->label('Your Saved Signature')
                            ->content(new HtmlString('<div style="background:#fff; padding:10px; border-radius:8px; border:1px solid #ddd; max-width: 250px;"><img src="' . $user->signature . '" style="max-height: 80px;" /></div>'));
                            
                        $formFields[] = Forms\Components\Toggle::make('use_saved_signature')
                            ->label('Use my saved profile signature')
                            ->default(true)
                            ->live();
                    }

                    // Show pad if no saved signature OR if use_saved_signature is false
                    $formFields[] = SignaturePad::make('drawn_signature')
                        ->label('Draw Signature')
                        ->backgroundColor('rgb(255, 255, 255)')
                        ->penColor('rgb(15, 23, 42)')
                        ->visible(fn ($get) => !($get('use_saved_signature') ?? false))
                        ->required(fn ($get) => !($get('use_saved_signature') ?? false));

                    if ($user && !$user->signature) {
                        $formFields[] = Forms\Components\Checkbox::make('save_to_profile')
                            ->label('Save this signature to my profile for future use')
                            ->default(true);
                    }

                    return $formFields;
                })
                ->action(function (PoSupplier $record, array $data) {
                    $user = auth()->user();
                    $signature = null;

                    if ($data['use_saved_signature'] ?? false) {
                        $signature = $user->signature;
                    } else {
                        $signature = $data['drawn_signature'] ?? null;
                        
                        // Save to profile if checkbox checked
                        if ($signature && ($data['save_to_profile'] ?? false)) {
                            $user->update(['signature' => $signature]);
                        }
                    }

                    if (!$signature) {
                        Notification::make()->title('Signature is required!')->danger()->send();
                        return;
                    }

                    // Find and update active level
                    $activeLevel = $record->approvals()->where('status', 'pending')->orderBy('id', 'asc')->first();
                    if ($activeLevel) {
                        $activeLevel->update([
                            'status' => 'approved',
                            'user_id' => $user->id,
                            'signature_path' => $signature,
                            'actioned_at' => now(),
                        ]);
                    }

                    // Check if all levels are approved
                    $remainingPending = $record->approvals()->where('status', 'pending')->exists();
                    if (!$remainingPending) {
                        $record->update([
                            'approval_status' => 'approved',
                            'status' => 'ordered', // automatically set PO status to ordered
                        ]);
                    }

                    Notification::make()
                        ->title('Purchase Order approved & signed successfully!')
                        ->success()
                        ->send();

                    return redirect()->to(PoSupplierResource::getUrl('edit', ['record' => $record]));
                }),

            // Reject action
            Actions\Action::make('reject_approval')
                ->label('Reject')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(function (PoSupplier $record) {
                    if ($record->approval_status !== 'pending_approval') {
                        return false;
                    }
                    
                    // Find active level
                    $activeLevel = $record->approvals()->where('status', 'pending')->orderBy('id', 'asc')->first();
                    if (!$activeLevel) {
                        return false;
                    }

                    // Level 2 (director) can only be acted on if Level 1 (manager) is approved
                    if ($activeLevel->approval_level === 'director') {
                        $managerApproved = $record->approvals()->where('approval_level', 'manager')->where('status', 'approved')->exists();
                        if (!$managerApproved) {
                            return false;
                        }
                    }

                    return true;
                })
                ->form([
                    Forms\Components\Textarea::make('rejection_reason')
                        ->label('Reason for Rejection')
                        ->placeholder('Provide a brief description of why this PO is rejected.')
                        ->required(),
                ])
                ->action(function (PoSupplier $record, array $data) {
                    $user = auth()->user();
                    
                    // Find and update active level to rejected
                    $activeLevel = $record->approvals()->where('status', 'pending')->orderBy('id', 'asc')->first();
                    if ($activeLevel) {
                        $activeLevel->update([
                            'status' => 'rejected',
                            'user_id' => $user->id,
                            'rejection_reason' => $data['rejection_reason'],
                            'actioned_at' => now(),
                        ]);
                    }

                    // Update PO status to rejected
                    $record->update([
                        'approval_status' => 'rejected',
                    ]);

                    Notification::make()
                        ->title('PO has been rejected.')
                        ->danger()
                        ->send();

                    return redirect()->to(PoSupplierResource::getUrl('edit', ['record' => $record]));
                }),

            // Revise action
            Actions\Action::make('revise_po')
                ->label('Revise')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (PoSupplier $record) => $record->approval_status === 'rejected')
                ->action(function (PoSupplier $record) {
                    // Create dynamic revision number suffix to avoid unique constraint collisions
                    $baseNumber = preg_replace('/-R\d+$/', '', $record->po_number);
                    $newRevisionNumber = $record->revision_number + 1;
                    $newPoNumber = $baseNumber . '-R' . $newRevisionNumber;

                    // Duplicate PO record
                    $newPo = $record->replicate();
                    $newPo->po_number = $newPoNumber;
                    $newPo->approval_status = 'draft';
                    $newPo->status = 'draft';
                    $newPo->parent_id = $record->id;
                    $newPo->revision_number = $newRevisionNumber;
                    $newPo->save();

                    // Duplicate all items
                    foreach ($record->items as $item) {
                        $newItem = $item->replicate();
                        $newItem->po_supplier_id = $newPo->id;
                        $newItem->save();
                    }

                    Notification::make()
                        ->title("Revision PO Created: {$newPo->po_number}")
                        ->success()
                        ->send();

                    // Redirect to the edit page of the new revision
                    return redirect()->to(PoSupplierResource::getUrl('edit', ['record' => $newPo]));
                }),

            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->record->load('items');
        $this->record->recalculateTotals();
        $this->record->syncStatusFromItems();
    }
}
