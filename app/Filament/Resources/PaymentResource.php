<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Filters\SelectFilter;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationLabel = 'Payments';
    protected static ?string $modelLabel = 'Payment';
    protected static ?string $pluralModelLabel = 'Payments';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('payment_number')
                ->required()
                ->maxLength(50),
            Forms\Components\Select::make('invoice_type')
                ->options([
                    'purchase' => 'Purchase Invoice',
                    'sales' => 'Sales Invoice',
                ])
                ->required()
                ->reactive(),
            Forms\Components\Select::make('invoice_id')
                ->label('Invoice')
                ->options(function (callable $get) {
                    $type = $get('invoice_type');
                    if ($type === 'purchase') {
                        return \App\Models\InvoicePurchase::pluck('invoice_number', 'id');
                    } elseif ($type === 'sales') {
                        return \App\Models\InvoiceSales::pluck('invoice_number', 'id');
                    }
                    return [];
                })
                ->required(),
            Forms\Components\DatePicker::make('payment_date')
                ->default(now()->toDateString())
                ->required(),
            Forms\Components\TextInput::make('amount')
                ->numeric()
                ->required()
                ->prefix('IDR'),
            Forms\Components\Select::make('payment_method')
                ->options([
                    'bank_transfer' => 'Bank Transfer',
                    'cash' => 'Cash',
                    'check' => 'Check',
                    'credit' => 'Credit',
                ])
                ->default('bank_transfer')
                ->required(),
            Forms\Components\TextInput::make('reference_number')
                ->maxLength(100),
            Forms\Components\Textarea::make('notes')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('payment_number')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('invoice_type')->badge(),
            Tables\Columns\TextColumn::make('invoice.invoice_number')->label('Invoice Number'),
            Tables\Columns\TextColumn::make('payment_date')->date()->sortable(),
            Tables\Columns\TextColumn::make('amount')->numeric()->sortable(),
            Tables\Columns\TextColumn::make('payment_method'),
        ])
            ->filters([
                SelectFilter::make('payment_method')->options([
                    'bank_transfer' => 'Bank Transfer',
                    'cash' => 'Cash',
                    'check' => 'Check',
                    'credit' => 'Credit',
                ]),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-credit-card';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Finance & Invoices';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
