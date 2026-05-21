<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesOrderResource\Pages;
use App\Models\SalesOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Filters\SelectFilter;

class SalesOrderResource extends Resource
{
    protected static ?string $model = SalesOrder::class;



    protected static ?string $navigationLabel = 'Sales Order';
    protected static ?string $modelLabel = 'Sales Order';
    protected static ?string $pluralModelLabel = 'Sales Orders';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('company_id')
                ->relationship('company', 'name')
                ->required(),
            Forms\Components\TextInput::make('so_number')
                ->maxLength(50),
            Forms\Components\Select::make('customer_id')
                ->relationship('customer', 'name')
                ->required(),
            Forms\Components\DatePicker::make('order_date'),
            Forms\Components\DatePicker::make('delivery_date'),
            Forms\Components\Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'confirmed' => 'Confirmed',
                    'in_production' => 'In Production',
                    'shipped' => 'Shipped',
                    'delivered' => 'Delivered',
                    'cancelled' => 'Cancelled',
                ])
                ->default('draft'),
            Forms\Components\TextInput::make('currency')
                ->default('USD')
                ->maxLength(10),
            Forms\Components\TextInput::make('exchange_rate')
                ->numeric()
                ->default(1),
            Forms\Components\TextInput::make('subtotal')
                ->numeric()
                ->default(0),
            Forms\Components\TextInput::make('tax_pct')
                ->numeric()
                ->default(11)
                ->suffix('%'),
            Forms\Components\TextInput::make('tax_amount')
                ->numeric()
                ->default(0),
            Forms\Components\TextInput::make('total_amount')
                ->numeric()
                ->default(0),
            Forms\Components\TextInput::make('down_payment_pct')
                ->numeric()
                ->default(0)
                ->suffix('%'),
            Forms\Components\TextInput::make('down_payment_amount')
                ->numeric()
                ->default(0),
            Forms\Components\Select::make('payment_terms')
                ->options([
                    'cod' => 'COD',
                    'dp_30' => 'DP 30%',
                    'dp_50' => 'DP 50%',
                    'net_15' => 'Net 15',
                    'net_30' => 'Net 30',
                    'net_60' => 'Net 60',
                ]),
            Forms\Components\Textarea::make('notes')
                ->maxLength(65535)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('so_number')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('customer.name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('order_date')->date()->sortable(),
            Tables\Columns\TextColumn::make('delivery_date')->date(),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'draft' => 'gray',
                    'confirmed' => 'info',
                    'in_production' => 'warning',
                    'shipped' => 'primary',
                    'delivered' => 'success',
                    'cancelled' => 'danger',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('currency'),
            Tables\Columns\TextColumn::make('total_amount')->money('USD')->sortable(),
            Tables\Columns\TextColumn::make('down_payment_pct')->suffix('%'),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'confirmed' => 'Confirmed',
                    'in_production' => 'In Production',
                    'shipped' => 'Shipped',
                    'delivered' => 'Delivered',
                    'cancelled' => 'Cancelled',
                ]),
                SelectFilter::make('customer_id')->relationship('customer', 'name'),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }



    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-document-currency-dollar';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Sales & Shipping';
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesOrders::route('/'),
            'create' => Pages\CreateSalesOrder::route('/create'),
            'edit' => Pages\EditSalesOrder::route('/{record}/edit'),
        ];
    }
}
