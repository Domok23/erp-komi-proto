<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShipmentResource\Pages;
use App\Models\Shipment;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static ?string $navigationLabel = 'Shipments';

    protected static ?string $modelLabel = 'Shipment';

    protected static ?string $pluralModelLabel = 'Shipments';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Shipment Details')
                ->columnSpanFull()
                ->schema([
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
                        ->label(new HtmlString('Container Number <span title="Nomor kode identifikasi kontainer kargo penyewaan barang" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->maxLength(100),
                    Forms\Components\TextInput::make('bl_number')
                        ->label(new HtmlString('BL Number <span title="Nomor Bill of Lading (bukti kontrak pengangkutan kargo laut/udara)" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->maxLength(100),
                    Forms\Components\TextInput::make('carrier')
                        ->label(new HtmlString('Carrier <span title="Nama perusahaan ekspedisi atau maskapai pelayaran pengangkut barang" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->maxLength(255),
                    Forms\Components\TextInput::make('port_of_loading')
                        ->label(new HtmlString('Port of Loading <span title="Nama pelabuhan asal tempat kargo dimuat ke kapal/pesawat" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->maxLength(255),
                    Forms\Components\TextInput::make('port_of_discharge')
                        ->label(new HtmlString('Port of Discharge <span title="Nama pelabuhan tujuan tempat pembongkaran kargo kiriman" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->maxLength(255),
                    Forms\Components\DatePicker::make('etd')
                        ->label('ETD'),
                    Forms\Components\DatePicker::make('eta')
                        ->label('ETA'),
                    Forms\Components\TextInput::make('total_packages')
                        ->label(new HtmlString('Total Packages <span title="Jumlah total dus karton atau koli kemasan barang dikirim" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('total_gross_weight_kg')
                        ->label(new HtmlString('Total Gross Weight (Kg) <span title="Berat kotor total kiriman termasuk dus dan palet dalam kilogram" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->numeric()
                        ->step(0.01)
                        ->default(0),
                    Forms\Components\TextInput::make('total_volume_m3')
                        ->label(new HtmlString('Total Volume (m³) <span title="Volume total ruang kargo paket kiriman dalam meter kubik" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->numeric()
                        ->step(0.01)
                        ->default(0),
                    Forms\Components\TextInput::make('shipping_cost_usd')
                        ->label(new HtmlString('Shipping Cost (USD) <span title="Biaya logistik kargo internasional yang dibayarkan dalam USD" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->numeric()
                        ->prefix('$')
                        ->default(0),
                    Forms\Components\Textarea::make('notes')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                ])
                ->columns(2),
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
            Tables\Columns\TextColumn::make('total_packages')
                ->numeric(decimalPlaces: 0, decimalSeparator: '.', thousandsSeparator: ','),
            Tables\Columns\TextColumn::make('shipping_cost_usd')->money('USD')->sortable(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('status')->options(['pending' => 'Pending', 'in_transit' => 'In Transit', 'customs' => 'Customs', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled']),
                SelectFilter::make('shipping_method')->options(['sea' => 'Sea', 'air' => 'Air', 'land' => 'Land', 'courier' => 'Courier']),
            ])
            ->actions([
                ActionGroup::make([
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
