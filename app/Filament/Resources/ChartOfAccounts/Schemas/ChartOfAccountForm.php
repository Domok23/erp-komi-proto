<?php

namespace App\Filament\Resources\ChartOfAccounts\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class ChartOfAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Account Details')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('account_code')
                            ->label('Account Code')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('account_name')
                            ->label('Account Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                        Forms\Components\Select::make('account_type')
                            ->label('Account Type')
                            ->options([
                                'asset' => 'Asset',
                                'liability' => 'Liability',
                                'equity' => 'Equity',
                                'revenue' => 'Revenue',
                                'expense' => 'Expense',
                            ])
                            ->required()
                            ->reactive()
                            ->columnSpan(1),
                        Forms\Components\Select::make('parent_id')
                            ->label('Parent Account')
                            ->relationship('parent', 'account_name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('balance')
                            ->label('Balance')
                            ->numeric()
                            ->default(0)
                            ->prefix('IDR')
                            ->disabled()
                            ->columnSpan(1),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->columnSpan(1),
                    ])
                    ->columns(2),
            ]);
    }
}
