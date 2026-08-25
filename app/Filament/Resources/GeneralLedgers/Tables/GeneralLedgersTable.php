<?php

namespace App\Filament\Resources\GeneralLedgers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GeneralLedgersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['debitAccount', 'creditAccount']))
            ->columns([
                Tables\Columns\TextColumn::make('entry_number')
                    ->label('Entry Number')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('entry_date')
                    ->label('Entry Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('debitAccount.account_name')
                    ->label('Debit Account')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('creditAccount.account_name')
                    ->label('Credit Account')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('debit_amount')
                    ->label('Debit Amount')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('credit_amount')
                    ->label('Credit Amount')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference_type')
                    ->label('Reference Type')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('reference_type')
                    ->options([
                        'invoice' => 'Invoice',
                        'po' => 'Purchase Order',
                        'payment' => 'Payment',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('entry_date', 'desc');
    }
}
