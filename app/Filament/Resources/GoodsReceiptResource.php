<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GoodsReceiptResource\Pages;
use App\Models\GoodsReceipt;
use App\Services\CodeGenerator;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Filters\SelectFilter;

class GoodsReceiptResource extends Resource
{
    protected static ?string $model = GoodsReceipt::class;

    protected static ?string $navigationLabel = 'Goods Receipt';
    protected static ?string $modelLabel = 'Goods Receipt';
    protected static ?string $pluralModelLabel = 'Goods Receipts';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('gr_number')
                ->default(fn () => CodeGenerator::generateGRNumber())
                ->disabled()
                ->dehydrated()
                ->required()
                ->maxLength(50),
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
                        return \App\Models\PoSupplier::pluck('po_number', 'id');
                    } elseif ($type === 'subcon') {
                        return \App\Models\PoSubcon::pluck('po_number', 'id');
                    }
                    return [];
                })
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    $type = $get('po_type');
                    if ($type === 'supplier') {
                        $po = \App\Models\PoSupplier::find($state, ['*']);
                        if ($po) {
                            $items = [];
                            foreach ($po->items as $item) {
                                $items[] = [
                                    'material_id' => $item->material_id,
                                    'qty_ordered' => $item->qty,
                                    'qty_received' => $item->qty - $item->qty_received,
                                    'qty_rejected' => 0,
                                    'unit' => $item->unit,
                                    'notes' => '',
                                ];
                            }
                            $set('items', $items);
                        }
                    }
                }),
            Forms\Components\Select::make('warehouse_id')
                ->relationship('warehouse', 'name')
                ->required(),
            Forms\Components\DatePicker::make('receipt_date')
                ->default(now()->toDateString())
                ->required(),
            Forms\Components\Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'received' => 'Received',
                    'partial' => 'Partial',
                    'verified' => 'Verified',
                ])
                ->default('draft')
                ->required(),
            Forms\Components\TextInput::make('received_by')
                ->maxLength(255),
            Forms\Components\Textarea::make('notes')
                ->maxLength(65535)
                ->columnSpanFull(),

            Section::make('Received Items')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Forms\Components\Select::make('material_id')
                                ->relationship('material', 'name')
                                ->disabled()
                                ->dehydrated()
                                ->required(),
                            Forms\Components\TextInput::make('qty_ordered')
                                ->numeric()
                                ->disabled()
                                ->dehydrated()
                                ->required(),
                            Forms\Components\TextInput::make('qty_received')
                                ->numeric()
                                ->required(),
                            Forms\Components\TextInput::make('qty_rejected')
                                ->numeric()
                                ->default(0),
                            Forms\Components\TextInput::make('unit')
                                ->disabled()
                                ->dehydrated(),
                            Forms\Components\TextInput::make('notes'),
                        ])
                        ->columns(3)
                        ->columnSpanFull(),
                ]),

            Section::make('Shipping Details')
                ->relationship('shipping')
                ->schema([
                    Forms\Components\TextInput::make('carrier'),
                    Forms\Components\TextInput::make('tracking_number'),
                    Forms\Components\TextInput::make('shipping_cost')
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('received_condition')
                        ->default('good'),
                    Forms\Components\TextInput::make('notes'),
                ])->columns(2),

            Section::make('Returns Handling')
                ->schema([
                    Forms\Components\Repeater::make('returs')
                        ->relationship('returs')
                        ->schema([
                            Forms\Components\TextInput::make('retur_number')->required(),
                            Forms\Components\Select::make('material_id')
                                ->relationship('material', 'name')
                                ->required(),
                            Forms\Components\TextInput::make('qty_returned')
                                ->numeric()
                                ->required(),
                            Forms\Components\TextInput::make('reason')
                                ->required(),
                            Forms\Components\Select::make('status')
                                ->options([
                                    'pending' => 'Pending',
                                    'shipped' => 'Shipped',
                                    'resolved' => 'Resolved',
                                    'cancelled' => 'Cancelled',
                                ])
                                ->default('pending')
                                ->required(),
                        ])
                        ->columns(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('gr_number')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('po_type')->badge(),
            Tables\Columns\TextColumn::make('po.po_number')->label('PO Number')->searchable(),
            Tables\Columns\TextColumn::make('warehouse.name')->sortable(),
            Tables\Columns\TextColumn::make('receipt_date')->date()->sortable(),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'draft' => 'gray',
                    'received' => 'info',
                    'partial' => 'warning',
                    'verified' => 'success',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('received_by'),
        ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'received' => 'Received',
                    'partial' => 'Partial',
                    'verified' => 'Verified',
                ]),
                SelectFilter::make('warehouse_id')->relationship('warehouse', 'name'),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-inbox-arrow-down';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Procurement';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGoodsReceipts::route('/'),
            'create' => Pages\CreateGoodsReceipt::route('/create'),
            'edit' => Pages\EditGoodsReceipt::route('/{record}/edit'),
        ];
    }
}
