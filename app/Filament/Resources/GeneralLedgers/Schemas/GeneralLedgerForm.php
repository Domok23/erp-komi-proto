<?php

namespace App\Filament\Resources\GeneralLedgers\Schemas;

use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GeneralLedgerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Ledger Details')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('entry_number')
                            ->label('Entry Number')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'GL-'.date('Ymd').'-'.str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT))
                            ->columnSpan(1),
                        Forms\Components\DatePicker::make('entry_date')
                            ->label('Entry Date')
                            ->required()
                            ->default(now())
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('description')
                            ->label('Description')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        Forms\Components\Select::make('debit_account_id')
                            ->label('Debit Account')
                            ->relationship('debitAccount', 'account_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(1),
                        Forms\Components\Select::make('credit_account_id')
                            ->label('Credit Account')
                            ->relationship('creditAccount', 'account_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('debit_amount')
                            ->label('Debit Amount')
                            ->numeric()
                            ->step(0.01)
                            ->required()
                            ->prefix('IDR')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('credit_amount', $state);
                            })
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('credit_amount')
                            ->label('Credit Amount')
                            ->numeric()
                            ->step(0.01)
                            ->required()
                            ->prefix('IDR')
                            ->disabled()
                            ->columnSpan(1),
                        Forms\Components\Select::make('reference_type')
                            ->label('Reference Type')
                            ->options([
                                'invoice' => 'Invoice',
                                'po' => 'Purchase Order',
                                'payment' => 'Payment',
                            ])
                            ->nullable()
                            ->reactive()
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('reference_id')
                            ->label('Reference ID')
                            ->numeric()
                            ->nullable()
                            ->columnSpan(1),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
