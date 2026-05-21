<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Models\PurchaseOrder;
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

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;



    protected static ?string $navigationLabel = 'Purchase Order';
    protected static ?string $modelLabel = 'Purchase Order';
    protected static ?string $pluralModelLabel = 'Purchase Orders';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('company_id')
                ->relationship('company', 'name')
                ->required(),
            Forms\Components\TextInput::make('po_number')
                ->maxLength(50),
            Forms\Components\Select::make('type')
                ->options([
                    'supplier' => 'Supplier',
                    'subcon' => 'Subcontractor',
                    'shipping' => 'Shipping',
                ])
                ->default('supplier'),
            Forms\Components\Select::make('supplier_id')
                ->relationship('supplier', 'name')
                ->nullable(),
            Forms\Components\Select::make('subcon_id')
                ->relationship('subcon', 'name')
                ->nullable(),
            Forms\Components\DatePicker::make('order_date'),
            Forms\Components\DatePicker::make('delivery_date'),
            Forms\Components\Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'sent' => 'Sent',
                    'confirmed' => 'Confirmed',
                    'partial' => 'Partial',
                    'received' => 'Received',
                    'cancelled' => 'Cancelled',
                ])
                ->default('draft'),
            Forms\Components\TextInput::make('currency')
                ->default('USD')
                ->maxLength(10),
            Forms\Components\TextInput::make('subtotal')
                ->numeric()
                ->default(0),
            Forms\Components\TextInput::make('tax_amount')
                ->numeric()
                ->default(0),
            Forms\Components\TextInput::make('total_amount')
                ->numeric()
                ->default(0),
            Forms\Components\Textarea::make('notes')
                ->maxLength(65535)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('po_number')->sortable()->searchable(),
            Tables\Columns\BadgeColumn::make('type')
                ->colors([
                    'primary' => 'supplier',
                    'warning' => 'subcon',
                    'info' => 'shipping',
                ]),
            Tables\Columns\TextColumn::make('supplier.name')->searchable(),
            Tables\Columns\TextColumn::make('subcon.name')->searchable(),
            Tables\Columns\TextColumn::make('order_date')->date()->sortable(),
            Tables\Columns\TextColumn::make('delivery_date')->date(),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'draft' => 'gray',
                    'sent' => 'info',
                    'confirmed' => 'primary',
                    'partial' => 'warning',
                    'received' => 'success',
                    'cancelled' => 'danger',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('currency'),
            Tables\Columns\TextColumn::make('total_amount')->money('USD')->sortable(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('type')->options(['supplier' => 'Supplier', 'subcon' => 'Subcontractor', 'shipping' => 'Shipping']),
                SelectFilter::make('status')->options(['draft' => 'Draft', 'sent' => 'Sent', 'confirmed' => 'Confirmed', 'partial' => 'Partial', 'received' => 'Received', 'cancelled' => 'Cancelled']),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }



    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-shopping-cart';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Procurement';
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
