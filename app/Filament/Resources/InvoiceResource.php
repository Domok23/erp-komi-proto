<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
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

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;



    protected static ?string $navigationLabel = 'Invoice';
    protected static ?string $modelLabel = 'Invoice';
    protected static ?string $pluralModelLabel = 'Invoices';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
                        Forms\Components\TextInput::make('invoice_number')
                ->maxLength(50),
            Forms\Components\Select::make('type')
                ->options([
                    'sales' => 'Sales',
                    'purchase' => 'Purchase',
                ])
                ->default('sales'),
            Forms\Components\Select::make('sales_order_id')
                ->relationship('salesOrder', 'so_number')
                ->nullable(),
            Forms\Components\Select::make('purchase_order_id')
                ->relationship('purchaseOrder', 'po_number')
                ->nullable(),
            Forms\Components\Select::make('customer_id')
                ->relationship('customer', 'name')
                ->nullable(),
            Forms\Components\Select::make('supplier_id')
                ->relationship('supplier', 'name')
                ->nullable(),
            Forms\Components\DatePicker::make('invoice_date'),
            Forms\Components\DatePicker::make('due_date'),
            Forms\Components\Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'sent' => 'Sent',
                    'paid' => 'Paid',
                    'overdue' => 'Overdue',
                    'cancelled' => 'Cancelled',
                ])
                ->default('draft'),
            Forms\Components\TextInput::make('currency')
                ->default('USD')
                ->maxLength(10),
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
            Forms\Components\TextInput::make('shipping_cost')
                ->numeric()
                ->default(0),
            Forms\Components\TextInput::make('total_amount')
                ->numeric()
                ->default(0),
            Forms\Components\Toggle::make('is_tax_invoice')
                ->default(false),
            Forms\Components\TextInput::make('tax_invoice_number')
                ->maxLength(100),
            Forms\Components\Textarea::make('notes')
                ->maxLength(65535)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('invoice_number')->sortable()->searchable(),
            Tables\Columns\BadgeColumn::make('type')
                ->colors(['primary' => 'sales', 'warning' => 'purchase']),
            Tables\Columns\TextColumn::make('customer.name')->searchable(),
            Tables\Columns\TextColumn::make('supplier.name')->searchable(),
            Tables\Columns\TextColumn::make('invoice_date')->date()->sortable(),
            Tables\Columns\TextColumn::make('due_date')->date(),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'draft' => 'gray',
                    'sent' => 'info',
                    'paid' => 'success',
                    'overdue' => 'danger',
                    'cancelled' => 'danger',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('currency'),
            Tables\Columns\TextColumn::make('total_amount')->money('USD')->sortable(),
            Tables\Columns\IconColumn::make('is_tax_invoice')->boolean(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('type')->options(['sales' => 'Sales', 'purchase' => 'Purchase']),
                SelectFilter::make('status')->options(['draft' => 'Draft', 'sent' => 'Sent', 'paid' => 'Paid', 'overdue' => 'Overdue', 'cancelled' => 'Cancelled']),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }



    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-banknotes';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Sales & Shipping';
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
