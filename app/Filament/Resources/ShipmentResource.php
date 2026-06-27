<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShipmentResource\Pages;
use App\Models\Shipment;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static ?string $navigationLabel = 'Shipments';

    protected static ?string $modelLabel = 'Shipment';

    protected static ?string $pluralModelLabel = 'Shipments';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('shipment_number')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(50),
            Forms\Components\Select::make('sales_order_id')
                ->relationship('salesOrder', 'so_number')
                ->searchable()
                ->preload()
                ->nullable(),
            Forms\Components\DatePicker::make('shipment_date')
                ->required(),
            Forms\Components\Select::make('status')
                ->options([
                    'pending' => 'Pending',
                    'in_transit' => 'In Transit',
                    'customs' => 'Customs',
                    'delivered' => 'Delivered',
                    'cancelled' => 'Cancelled',
                ])
                ->required()
                ->default('pending'),
            Forms\Components\Select::make('shipping_method')
                ->options([
                    'sea' => 'Sea',
                    'air' => 'Air',
                    'land' => 'Land',
                    'courier' => 'Courier',
                ])
                ->required()
                ->default('sea'),
            Forms\Components\TextInput::make('container_number')
                ->maxLength(100),
            Forms\Components\TextInput::make('bl_number')
                ->label('BL Number')
                ->maxLength(100),
            Forms\Components\TextInput::make('carrier')
                ->maxLength(255),
            Forms\Components\TextInput::make('port_of_loading')
                ->maxLength(255),
            Forms\Components\TextInput::make('port_of_discharge')
                ->maxLength(255),
            Forms\Components\DatePicker::make('etd')
                ->label('ETD'),
            Forms\Components\DatePicker::make('eta')
                ->label('ETA'),
            Forms\Components\TextInput::make('total_packages')
                ->numeric()
                ->default(0),
            Forms\Components\TextInput::make('total_gross_weight_kg')
                ->numeric()
                ->step(0.01)
                ->default(0),
            Forms\Components\TextInput::make('total_volume_m3')
                ->numeric()
                ->step(0.01)
                ->default(0),
            Forms\Components\TextInput::make('shipping_cost_usd')
                ->numeric()
                ->prefix('$')
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
            Tables\Columns\TextColumn::make('shipment_number')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('salesOrder.so_number')->searchable(),
            Tables\Columns\TextColumn::make('shipment_date')->date()->sortable(),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'pending' => 'gray',
                    'in_transit' => 'info',
                    'customs' => 'warning',
                    'delivered' => 'success',
                    'cancelled' => 'danger',
                    default => 'gray',
                }),
            Tables\Columns\BadgeColumn::make('shipping_method')
                ->colors(['primary' => 'sea', 'info' => 'air', 'warning' => 'land', 'success' => 'courier']),
            Tables\Columns\TextColumn::make('carrier'),
            Tables\Columns\TextColumn::make('etd')->date(),
            Tables\Columns\TextColumn::make('eta')->date(),
            Tables\Columns\TextColumn::make('total_packages')->numeric(),
            Tables\Columns\TextColumn::make('shipping_cost_usd')->money('USD')->sortable(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('status')->options(['pending' => 'Pending', 'in_transit' => 'In Transit', 'customs' => 'Customs', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled']),
                SelectFilter::make('shipping_method')->options(['sea' => 'Sea', 'air' => 'Air', 'land' => 'Land', 'courier' => 'Courier']),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-paper-airplane';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Sales & Shipping';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShipments::route('/'),
            'create' => Pages\CreateShipment::route('/create'),
            'edit' => Pages\EditShipment::route('/{record}/edit'),
        ];
    }
}
