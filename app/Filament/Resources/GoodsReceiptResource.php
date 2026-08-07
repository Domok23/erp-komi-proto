<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GoodsReceiptResource\Pages;
use App\Models\GoodsReceipt;
use App\Models\InventoryStock;
use App\Models\Material;
use App\Models\PoSupplier;
use App\Models\PurchaseShipment;
use App\Services\CodeGenerator;
use App\Services\CompanyContext;
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
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

class GoodsReceiptResource extends Resource
{
    protected static ?string $model = GoodsReceipt::class;

    protected static ?string $navigationLabel = 'Goods Receipt';

    protected static ?string $modelLabel = 'Goods Receipt';

    protected static ?string $recordTitleAttribute = 'gr_number';

    protected static ?string $pluralModelLabel = 'Goods Receipts';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Goods Receipt Details')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('gr_number')
                        ->default(fn () => CodeGenerator::generateGRNumber())
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->maxLength(50),
                    Forms\Components\Select::make('po_id')
                        ->label('Purchase Order')
                        ->relationship('po', 'po_number')
                        ->getOptionLabelFromRecordUsing(fn ($record) => new HtmlString('<a href="'.PoSupplierResource::getUrl('edit', ['record' => $record]).'" class="ref-link">'.$record->po_number.'</a>'))
                        ->allowHtml()
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (! $state) {
                                $set('items', []);
                                $set('shipping', null);

                                return;
                            }
                            $po = PoSupplier::with('items')->find($state);
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

                            // Auto-populate shipping details from latest PurchaseShipment
                            $latestShipment = PurchaseShipment::where('po_type', 'supplier')
                                ->where('po_id', $state)
                                ->latest()
                                ->first();

                            if ($latestShipment) {
                                $set('shipping', [
                                    'carrier' => $latestShipment->carrier,
                                    'tracking_number' => $latestShipment->tracking_number,
                                    'shipping_cost' => $latestShipment->shipping_cost,
                                    'received_condition' => 'good',
                                    'notes' => 'Auto-populated from '.$latestShipment->shipment_number,
                                ]);
                            }
                        }),
                    Forms\Components\Select::make('warehouse_id')
                        ->relationship('warehouse', 'name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => new HtmlString('<a href="'.WarehouseResource::getUrl('edit', ['record' => $record]).'" class="ref-link">'.$record->name.'</a>'))
                        ->allowHtml()
                        ->searchable()
                        ->preload()
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
                ])
                ->columns(2),

            Section::make('Received Items')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Forms\Components\Select::make('material_id')
                                ->relationship('material', 'name')
                                ->getOptionLabelFromRecordUsing(function ($record) {
                                    $companyId = CompanyContext::getCompanyId();
                                    $stock = InventoryStock::where('material_id', $record->id)
                                        ->where('company_id', $companyId)
                                        ->sum('quantity');

                                    return "[{$record->code}] {$record->name} (Stock: ".number_format($stock, 2)." {$record->unit})";
                                })
                                ->disabled()
                                ->dehydrated()
                                ->required(),
                            Forms\Components\TextInput::make('qty_ordered')
                                ->disabled()
                                ->dehydrated()
                                ->required()
                                ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', ',') : $state)
                                ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),
                            Forms\Components\TextInput::make('qty_received')
                                ->numeric()
                                ->step(0.01)
                                ->required(),
                            Forms\Components\TextInput::make('qty_rejected')
                                ->numeric()
                                ->step(0.01)
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
                ->columnSpanFull()
                ->relationship('shipping')
                ->schema([
                    Forms\Components\TextInput::make('carrier'),
                    Forms\Components\TextInput::make('tracking_number'),
                    Forms\Components\TextInput::make('shipping_cost')
                        ->numeric()
                        ->step(0.01)
                        ->default(0),
                    Forms\Components\TextInput::make('received_condition')
                        ->default('good'),
                    Forms\Components\TextInput::make('notes'),
                ])->columns(2),

            Section::make('Returns Handling')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Repeater::make('returs')
                        ->relationship('returs')
                        ->schema([
                            Forms\Components\TextInput::make('retur_number')
                                ->default(function (callable $get) {
                                    $existingReturs = $get('../../returs') ?? [];
                                    $excludeNumbers = collect($existingReturs)
                                        ->pluck('retur_number')
                                        ->filter()
                                        ->toArray();

                                    return CodeGenerator::generateGRReturNumber($excludeNumbers);
                                })
                                ->live(onBlur: true)
                                ->dehydrated()
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
                            Forms\Components\Textarea::make('notes')
                                ->columnSpanFull(),
                            Forms\Components\Repeater::make('items')
                                ->relationship('items')
                                ->label('Return Items')
                                ->schema([
                                    Forms\Components\Select::make('material_id')
                                        ->label('Material')
                                        ->options(function (callable $get) {
                                            $grItems = $get('../../../../items') ?? [];
                                            $materialIds = collect($grItems)
                                                ->pluck('material_id')
                                                ->filter()
                                                ->unique()
                                                ->values();

                                            if ($materialIds->isEmpty()) {
                                                return [];
                                            }

                                            return Material::whereIn('id', $materialIds)
                                                ->get()
                                                ->mapWithKeys(fn ($material) => [
                                                    $material->id => "[{$material->code}] {$material->name}",
                                                ]);
                                        })
                                        ->searchable()
                                        ->required(),
                                    Forms\Components\TextInput::make('qty_returned')
                                        ->numeric()
                                        ->step(0.01)
                                        ->required()
                                        ->minValue(0.01)
                                        ->rules([
                                            fn ($get) => function (string $attribute, $value, $fail) use ($get) {
                                                $materialId = $get('material_id');
                                                if (! $materialId) {
                                                    return;
                                                }
                                                $grItems = $get('../../../../items') ?? [];
                                                $matchedItem = collect($grItems)->firstWhere('material_id', $materialId);
                                                $maxAllowed = $matchedItem ? floatval($matchedItem['qty_received'] ?? 0) : 0;

                                                if (floatval($value) > $maxAllowed) {
                                                    $fail("Kuantitas yang diretur ({$value}) tidak boleh melebihi kuantitas yang diterima ({$maxAllowed}).");
                                                }
                                            },
                                        ]),
                                    Forms\Components\TextInput::make('reason')
                                        ->required(),
                                ])
                                ->columns(3)
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->columnSpanFull()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['retur_number'] ?? 'New Return'),
                ]),

            Section::make('Receiver Sign-off')
                ->columnSpanFull()
                ->description('Warehouse / receiver signature to confirm goods have been received.')
                ->schema([
                    SignaturePad::make('receiver_signature')
                        ->label('Receiver Signature')
                        ->hint('Draw signature to confirm receipt')
                        ->backgroundColor('rgb(255, 255, 255)')
                        ->penColor('rgb(15, 23, 42)')
                        ->downloadable()
                        ->nullable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('gr_number')->sortable()->searchable(),
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
            ->actions([
                ActionGroup::make([
                    \Filament\Actions\Action::make('downloadPdf')
                        ->label('Download PDF')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function ($record) {
                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.goods-receipt', [
                                'goodsReceipt' => $record,
                                'company' => $record->company,
                                'warehouse' => $record->warehouse,
                                'po' => $record->po,
                            ]);
                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                "goods-receipt-{$record->gr_number}.pdf"
                            );
                        }),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
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
