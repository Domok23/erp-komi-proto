<?php

namespace App\Filament\Resources\StockTransfers\Schemas;

use App\Models\InventoryStock;
use App\Models\Material;
use App\Models\Warehouse;
use App\Services\CodeGenerator;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockTransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('transfer_number')
                    ->default(fn () => CodeGenerator::generateTransferNumber())
                    ->disabled()
                    ->dehydrated()
                    ->required()
                    ->maxLength(50),
                DatePicker::make('transfer_date')
                    ->default(now()->toDateString())
                    ->required(),
                Select::make('from_company_id')
                    ->label('From Company')
                    ->relationship('fromCompany', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('from_warehouse_id', null)),
                Select::make('from_warehouse_id')
                    ->label('From Warehouse')
                    ->options(function (callable $get) {
                        $companyId = $get('from_company_id');
                        if (! $companyId) {
                            return [];
                        }

                        return Warehouse::withoutGlobalScope('company')->where('company_id', $companyId)->pluck('name', 'id');
                    })
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('items', [])),
                Select::make('to_company_id')
                    ->label('To Company')
                    ->relationship('toCompany', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('to_warehouse_id', null)),
                Select::make('to_warehouse_id')
                    ->label('To Warehouse')
                    ->options(function (callable $get) {
                        $companyId = $get('to_company_id');
                        if (! $companyId) {
                            return [];
                        }

                        return Warehouse::withoutGlobalScope('company')->where('company_id', $companyId)->pluck('name', 'id');
                    })
                    ->searchable()
                    ->required()
                    ->different('from_warehouse_id')
                    ->validationMessages([
                        'different' => 'Destination warehouse must be different from source warehouse.',
                    ]),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Pending Approval',
                        'shipped' => 'Shipped (Stock Deducted)',
                        'received' => 'Received (Stock Added)',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('draft')
                    ->required(),
                Textarea::make('notes')
                    ->columnSpanFull(),

                Section::make('Transfer Items')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Select::make('material_id')
                                    ->options(function (callable $get) {
                                        $fromWarehouseId = $get('../../from_warehouse_id');
                                        if (! $fromWarehouseId) {
                                            return [];
                                        }

                                        $currentMaterialId = $get('material_id');

                                        // Find materials that have stock > 0 in this warehouse, OR are currently selected
                                        $stocks = InventoryStock::withoutGlobalScope('company')
                                            ->where('warehouse_id', $fromWarehouseId)
                                            ->where(function ($q) use ($currentMaterialId) {
                                                $q->where('quantity', '>', 0);
                                                if ($currentMaterialId) {
                                                    $q->orWhere('material_id', $currentMaterialId);
                                                }
                                            })
                                            ->with('material')
                                            ->get();

                                        return $stocks->pluck('material')
                                            ->unique('id')
                                            ->filter()
                                            ->mapWithKeys(function ($material) use ($fromWarehouseId) {
                                                $qty = InventoryStock::withoutGlobalScope('company')
                                                    ->where('warehouse_id', $fromWarehouseId)
                                                    ->where('material_id', $material->id)
                                                    ->first()?->quantity ?? 0;

                                                return [$material->id => "[{$material->code}] {$material->name} (Stock: ".number_format($qty, 2)." {$material->unit})"];
                                            })
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $material = Material::find($state, ['*']);
                                        if ($material) {
                                            $set('unit', $material->unit);
                                        }
                                    }),
                                TextInput::make('qty_requested')
                                    ->label('Qty Requested')
                                    ->numeric()
                                    ->step(0.01)
                                    ->default(1)
                                    ->required(),
                                TextInput::make('qty_transferred')
                                    ->label('Qty Transferred')
                                    ->numeric()
                                    ->step(0.01)
                                    ->default(1)
                                    ->required()
                                    ->rules([
                                        fn (callable $get) => function (string $attribute, $value, $fail) use ($get) {
                                            $fromWarehouseId = $get('../../from_warehouse_id');
                                            $materialId = $get('material_id');
                                            if (! $fromWarehouseId || ! $materialId) {
                                                return;
                                            }

                                            $stock = InventoryStock::withoutGlobalScope('company')
                                                ->where('warehouse_id', $fromWarehouseId)
                                                ->where('material_id', $materialId)
                                                ->first()?->quantity ?? 0;

                                            if ($value > $stock) {
                                                $fail("Kuantitas transfer ({$value}) melebihi stok yang tersedia ({$stock}).");
                                            }
                                        },
                                    ]),
                                TextInput::make('unit')
                                    ->disabled()
                                    ->dehydrated()
                                    ->default('pcs'),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
