<?php

namespace App\Filament\Resources\ChartOfAccounts\Tables;

use App\Models\ChartOfAccount;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ChartOfAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('parent'))
            ->columns([
                Tables\Columns\TextColumn::make('account_code')
                    ->label('Code')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('account_name')
                    ->label('Account Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('account_type')
                    ->label('Type')
                    ->badge()
                    ->colors([
                        'success' => 'asset',
                        'warning' => 'liability',
                        'info' => 'equity',
                        'primary' => 'revenue',
                        'danger' => 'expense',
                    ]),
                Tables\Columns\TextColumn::make('parent.account_name')
                    ->label('Parent Account')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('account_type')
                    ->options([
                        'asset' => 'Asset',
                        'liability' => 'Liability',
                        'equity' => 'Equity',
                        'revenue' => 'Revenue',
                        'expense' => 'Expense',
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make()
                        ->before(function (ChartOfAccount $record, DeleteAction $action) {
                            $blockers = $record->getDeletionBlockers();
                            if (! empty($blockers)) {
                                Notification::make()
                                    ->title('Cannot Delete Account')
                                    ->body("Account '{$record->account_name}' [{$record->account_code}] cannot be deleted because it is linked to: ".implode(', ', $blockers).'.')
                                    ->danger()
                                    ->persistent()
                                    ->send();

                                $action->halt();
                            }
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (Collection $records, DeleteBulkAction $action) {
                            $blocked = [];
                            foreach ($records as $record) {
                                $blockers = $record->getDeletionBlockers();
                                if (! empty($blockers)) {
                                    $blocked[] = "{$record->account_code} (".implode(', ', $blockers).')';
                                }
                            }
                            if (! empty($blocked)) {
                                Notification::make()
                                    ->title('Cannot Delete Selected Accounts')
                                    ->body('Some accounts cannot be deleted: '.implode('; ', $blocked).'.')
                                    ->danger()
                                    ->persistent()
                                    ->send();

                                $action->halt();
                            }
                        }),
                ]),
            ])
            ->defaultSort('account_code');
    }
}
