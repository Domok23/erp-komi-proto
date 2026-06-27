<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubconMaterialInResource\Pages;
use App\Models\InventoryStock;
use App\Models\Material;
use App\Models\PoSubcon;
use App\Models\SubconMaterialIn;
use App\Models\SubconMaterialOut;
use App\Services\CompanyContext;
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

class SubconMaterialInResource extends Resource
{
    protected static ?string $model = SubconMaterialIn::class;

    protected static ?string $navigationLabel = 'Subcon Material In';

    protected static ?string $modelLabel = 'Subcon Material In';

    protected static ?string $pluralModelLabel = 'Subcon Material Ins';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('document_number')
                ->required()
                ->maxLength(50),
            Forms\Components\Select::make('po_subcon_id')
                ->relationship('poSubcon', 'po_number')
                ->searchable()
                ->preload()
                ->nullable()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    $po = $state ? PoSubcon::find($state, ['*']) : null;
                    if ($po) {
                        $set('subcon_id', $po->subcon_id);
                    } else {
                        $set('subcon_id', null);
                    }
                    $set('subcon_material_out_id', null);
                    $set('items', []);
                }),
            Forms\Components\Select::make('subcon_material_out_id')
                ->relationship('subconMaterialOut', 'document_number', function ($query, callable $get) {
                    $poId = $get('po_subcon_id');
                    if ($poId) {
                        return $query->where('po_subcon_id', $poId);
                    }

                    return $query;
                })
                ->label('Reference Material Out')
                ->searchable()
                ->preload()
                ->nullable()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    if (!$state) {
                        $set('items', []);
                        return;
                    }
                    $out = SubconMaterialOut::with('items')->find($state);
                    if ($out) {
                        $set('subcon_id', $out->subcon_id);
                        $set('po_subcon_id', $out->po_subcon_id);

                        $items = [];
                        
                        // 1. Load Processed Goods / Services from PoSubcon
                        if ($out->po_subcon_id) {
                            $po = PoSubcon::with('items')->find($out->po_subcon_id);
                            if ($po) {
                                foreach ($po->items as $poItem) {
                                    $items[] = [
                                        'item_type' => 'processed',
                                        'material_id' => null,
                                        'description' => $poItem->description,
                                        'qty_received' => $poItem->qty,
                                        'qty_rejected' => 0.00,
                                        'unit' => 'pcs',
                                    ];
                                }
                            }
                        }

                        // 2. Load Raw Materials from SubconMaterialOut
                        foreach ($out->items as $outItem) {
                            $items[] = [
                                'item_type' => 'raw_return',
                                'material_id' => $outItem->material_id,
                                'description' => null,
                                'qty_received' => 0.00,
                                'qty_rejected' => 0.00,
                                'unit' => $outItem->unit,
                            ];
                        }

                        $set('items', $items);
                    }
                }),
            Forms\Components\Select::make('subcon_id')
                ->relationship('subcon', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\DatePicker::make('receive_date')
                ->default(now()->toDateString())
                ->required(),
            Forms\Components\Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'received' => 'Received',
                    'verified' => 'Verified',
                ])
                ->default('draft')
                ->required(),
            Forms\Components\Textarea::make('notes')
                ->columnSpanFull(),

            Section::make('Received Materials')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Forms\Components\Select::make('item_type')
                                ->options([
                                    'processed' => 'Barang Hasil Olahan',
                                    'raw_return' => 'Sisa Bahan Baku/Reject',
                                ])
                                ->default('processed')
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $set('material_id', null);
                                    $set('description', null);
                                    $set('unit', 'pcs');
                                }),
                            Forms\Components\TextInput::make('description')
                                ->label('Material / Service Description')
                                ->required(fn (callable $get) => $get('item_type') === 'processed')
                                ->visible(fn (callable $get) => $get('item_type') === 'processed')
                                ->maxLength(255),
                            Forms\Components\Select::make('material_id')
                                ->relationship('material', 'name')
                                ->getOptionLabelFromRecordUsing(function ($record) {
                                    $companyId = CompanyContext::getCompanyId();
                                    $stock = InventoryStock::where('material_id', $record->id)
                                        ->where('company_id', $companyId)
                                        ->sum('quantity');

                                    return "[{$record->code}] {$record->name} (Stock: ".number_format($stock, 2)." {$record->unit})";
                                })
                                ->searchable()
                                ->preload()
                                ->required(fn (callable $get) => $get('item_type') === 'raw_return')
                                ->visible(fn (callable $get) => $get('item_type') === 'raw_return')
                                ->reactive()
                                ->disabled(fn (callable $get) => $get('item_type') === 'raw_return' && $get('material_id') !== null)
                                ->dehydrated()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $material = Material::find($state, ['*']);
                                    if ($material) {
                                        $set('unit', $material->unit);
                                    }
                                }),
                            Forms\Components\TextInput::make('qty_received')
                                ->label(fn (callable $get) => $get('item_type') === 'raw_return' ? 'Sisa Bahan Baku Kembali' : 'Qty Barang Hasil Diterima')
                                ->numeric()
                                ->default(1)
                                ->required()
                                ->minValue(0.01)
                                ->rules([
                                    fn ($get) => function (string $attribute, $value, $fail) use ($get) {
                                        if ($get('item_type') !== 'raw_return') {
                                            return;
                                        }
                                        $materialId = $get('material_id');
                                        if (!$materialId) {
                                            return;
                                        }
                                        $outId = $get('../../subcon_material_out_id');
                                        if (!$outId) {
                                            return;
                                        }
                                        $out = SubconMaterialOut::with('items')->find($outId);
                                        if (!$out) {
                                            return;
                                        }
                                        $outItem = $out->items->firstWhere('material_id', $materialId);
                                        $maxSent = $outItem ? floatval($outItem->qty_sent) : 0;
                                        
                                        $qtyReceived = floatval($value);
                                        $qtyRejected = floatval($get('qty_rejected') ?? 0);
                                        
                                        if (($qtyReceived + $qtyRejected) > $maxSent) {
                                            $fail("Total barang sisa ({$qtyReceived}) dan reject ({$qtyRejected}) tidak boleh melebihi jumlah yang dikirim ({$maxSent}).");
                                        }
                                    }
                                 ]),
                            Forms\Components\TextInput::make('qty_rejected')
                                ->label(fn (callable $get) => $get('item_type') === 'raw_return' ? 'Bahan Baku Rusak/Reject' : 'Qty Barang Hasil Reject')
                                ->numeric()
                                ->default(0)
                                ->required()
                                ->minValue(0.00)
                                ->rules([
                                    fn ($get) => function (string $attribute, $value, $fail) use ($get) {
                                        if ($get('item_type') !== 'raw_return') {
                                            return;
                                        }
                                        $materialId = $get('material_id');
                                        if (!$materialId) {
                                            return;
                                        }
                                        $outId = $get('../../subcon_material_out_id');
                                        if (!$outId) {
                                            return;
                                        }
                                        $out = SubconMaterialOut::with('items')->find($outId);
                                        if (!$out) {
                                            return;
                                        }
                                        $outItem = $out->items->firstWhere('material_id', $materialId);
                                        $maxSent = $outItem ? floatval($outItem->qty_sent) : 0;
                                        
                                        $qtyReceived = floatval($get('qty_received') ?? 0);
                                        $qtyRejected = floatval($value);
                                        
                                        if (($qtyReceived + $qtyRejected) > $maxSent) {
                                            $fail("Total barang sisa ({$qtyReceived}) dan reject ({$qtyRejected}) tidak boleh melebihi jumlah yang dikirim ({$maxSent}).");
                                        }
                                    }
                                 ]),
                            Forms\Components\TextInput::make('unit')
                                ->disabled()
                                ->dehydrated()
                                ->default('pcs'),
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
            Tables\Columns\TextColumn::make('document_number')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('poSubcon.po_number')->label('Subcon PO'),
            Tables\Columns\TextColumn::make('subconMaterialOut.document_number')->label('Material Out Ref'),
            Tables\Columns\TextColumn::make('subcon.name')->sortable(),
            Tables\Columns\TextColumn::make('receive_date')->date()->sortable(),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'draft' => 'gray',
                    'received' => 'info',
                    'verified' => 'success',
                    default => 'gray',
                }),
        ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'received' => 'Received',
                    'verified' => 'Verified',
                ]),
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
        return 'heroicon-o-arrow-down-on-square';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Inventory & Subcon';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubconMaterialIns::route('/'),
            'create' => Pages\CreateSubconMaterialIn::route('/create'),
            'edit' => Pages\EditSubconMaterialIn::route('/{record}/edit'),
        ];
    }
}
