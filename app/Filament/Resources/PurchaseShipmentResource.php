<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseShipmentResource\Pages;
use App\Models\PoSubcon;
use App\Models\PoSupplier;
use App\Models\PurchaseShipment;
use App\Services\CodeGenerator;
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

class PurchaseShipmentResource extends Resource
{
    protected static ?string $model = PurchaseShipment::class;

    protected static ?string $navigationLabel = 'Purchase Shipments';

    protected static ?string $modelLabel = 'Purchase Shipment';

    protected static ?string $pluralModelLabel = 'Purchase Shipments';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Shipment Details')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('shipment_number')
                        ->default(fn () => CodeGenerator::generatePurchaseShipmentNo())
                        ->disabled()
                        ->dehydrated()
                        ->required(),
                    Forms\Components\Select::make('po_type')
                        ->options([
                            'supplier' => 'Supplier PO',
                            'subcon' => 'Subcon PO',
                        ])
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(fn (callable $set) => $set('po_id', null)),
                    Forms\Components\Select::make('po_id')
                        ->label('Purchase Order')
                        ->options(function (callable $get) {
                            $type = $get('po_type');
                            if ($type === 'supplier') {
                                return PoSupplier::all()->mapWithKeys(function ($po) {
                                    $url = PoSupplierResource::getUrl('edit', ['record' => $po]);

                                    return [$po->id => '<a href="'.$url.'" class="ref-link">'.$po->po_number.'</a>'];
                                })->toArray();
                            } elseif ($type === 'subcon') {
                                return PoSubcon::all()->mapWithKeys(function ($po) {
                                    $url = PoSubconResource::getUrl('edit', ['record' => $po]);

                                    return [$po->id => '<a href="'.$url.'" class="ref-link">'.$po->po_number.'</a>'];
                                })->toArray();
                            }

                            return [];
                        })
                        ->getOptionLabelUsing(function ($value, callable $get) {
                            if (! $value) {
                                return null;
                            }
                            $type = $get('po_type');
                            if ($type === 'supplier') {
                                $po = PoSupplier::find($value);
                                if ($po) {
                                    $url = PoSupplierResource::getUrl('edit', ['record' => $po]);

                                    return new HtmlString('<a href="'.$url.'" class="ref-link">'.$po->po_number.'</a>');
                                }
                            } elseif ($type === 'subcon') {
                                $po = PoSubcon::find($value);
                                if ($po) {
                                    $url = PoSubconResource::getUrl('edit', ['record' => $po]);

                                    return new HtmlString('<a href="'.$url.'" class="ref-link">'.$po->po_number.'</a>');
                                }
                            }

                            return $value;
                        })
                        ->allowHtml()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                            if (! $state) {
                                $set('shipping_cost', 0);

                                return;
                            }
                            $type = $get('po_type');
                            if ($type === 'subcon') {
                                $po = PoSubcon::find($state);
                                if ($po) {
                                    $set('shipping_cost', $po->shipping_cost ?? 0);
                                }
                            } else {
                                $set('shipping_cost', 0);
                            }
                        }),
                    Forms\Components\DatePicker::make('shipment_date')
                        ->default(now()->toDateString())
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'shipped' => 'Shipped',
                            'in_transit' => 'In Transit',
                            'customs' => 'Customs Clearance',
                            'arrived' => 'Arrived',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('draft')
                        ->required(),
                    Forms\Components\Select::make('shipping_method')
                        ->options([
                            'sea' => 'Sea',
                            'air' => 'Air',
                            'land' => 'Land',
                            'courier' => 'Courier',
                        ]),
                    Forms\Components\TextInput::make('carrier')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('tracking_number')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('container_number')
                        ->maxLength(100),
                    Forms\Components\TextInput::make('bl_number')
                        ->label('BL Number')
                        ->maxLength(100),
                    Forms\Components\Textarea::make('notes')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Schedule & Cost')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\DatePicker::make('etd')->label('ETD'),
                    Forms\Components\DatePicker::make('eta')
                        ->label('ETA')
                        ->afterOrEqual('etd'),
                    Forms\Components\DatePicker::make('actual_arrival')
                        ->label('Actual Arrival')
                        ->afterOrEqual('shipment_date'),
                    Forms\Components\TextInput::make('total_packages')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),
                    Forms\Components\TextInput::make('total_gross_weight_kg')
                        ->numeric()
                        ->step(0.01)
                        ->default(0)
                        ->suffix('kg')
                        ->minValue(0),
                    Forms\Components\TextInput::make('total_volume_m3')
                        ->numeric()
                        ->step(0.01)
                        ->default(0)
                        ->suffix('m³')
                        ->minValue(0),
                    Forms\Components\TextInput::make('shipping_cost')
                        ->numeric()
                        ->default(0)
                        ->prefix('IDR')
                        ->minValue(0),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('shipment_number')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('po_type')->badge(),
            Tables\Columns\TextColumn::make('po.po_number')->label('PO Number')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('shipment_date')->date()->sortable(),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'draft' => 'gray',
                    'shipped' => 'info',
                    'in_transit' => 'warning',
                    'customs' => 'warning',
                    'arrived' => 'success',
                    'cancelled' => 'danger',
                    default => 'gray',
                }),
            Tables\Columns\BadgeColumn::make('shipping_method')
                ->colors([
                    'primary' => 'sea',
                    'info' => 'air',
                    'warning' => 'land',
                    'success' => 'courier',
                ]),
            Tables\Columns\TextColumn::make('carrier'),
            Tables\Columns\TextColumn::make('etd')->date(),
            Tables\Columns\TextColumn::make('eta')->date(),
            Tables\Columns\TextColumn::make('actual_arrival')->date(),
            Tables\Columns\TextColumn::make('shipping_cost')->money('IDR'),
        ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'shipped' => 'Shipped',
                    'in_transit' => 'In Transit',
                    'customs' => 'Customs Clearance',
                    'arrived' => 'Arrived',
                    'cancelled' => 'Cancelled',
                ]),
                SelectFilter::make('po_type')->options([
                    'supplier' => 'Supplier PO',
                    'subcon' => 'Subcon PO',
                ]),
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
        return 'heroicon-o-truck';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Procurement';
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
            'index' => Pages\ListPurchaseShipments::route('/'),
            'create' => Pages\CreatePurchaseShipment::route('/create'),
            'edit' => Pages\EditPurchaseShipment::route('/{record}/edit'),
        ];
    }
}
