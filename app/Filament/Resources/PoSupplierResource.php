<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PoSupplierResource\Pages;
use App\Models\InventoryStock;
use App\Models\Material;
use App\Models\PoSupplier;
use App\Services\CodeGenerator;
use App\Services\CompanyContext;
use App\Services\InvoiceGeneratorService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PoSupplierResource extends Resource
{
    protected static ?string $model = PoSupplier::class;

    protected static ?string $navigationLabel = 'PO Suppliers';

    protected static ?string $modelLabel = 'PO Supplier';

    protected static ?string $pluralModelLabel = 'PO Suppliers';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('po_number')
                ->default(fn () => CodeGenerator::generatePOSupplierNo())
                ->disabled()
                ->dehydrated()
                ->required(),
            Forms\Components\Select::make('project_id')
                ->relationship('project', 'project_code')
                ->searchable()
                ->preload()
                ->nullable(),
            Forms\Components\Select::make('supplier_id')
                ->relationship('supplier', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->reactive(),
            Forms\Components\DatePicker::make('po_date')
                ->default(now()->toDateString())
                ->required(),
            Forms\Components\DatePicker::make('delivery_date'),
            Forms\Components\Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'ordered' => 'Ordered',
                    'partial' => 'Partial',
                    'received' => 'Received',
                    'cancelled' => 'Cancelled',
                ])
                ->default('draft')
                ->required(),

            Section::make('Cost & Tax Totals')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('subtotal')
                        ->numeric()
                        ->default(0)
                        ->disabled()
                        ->dehydrated()
                        ->prefix('IDR'),
                    Forms\Components\TextInput::make('ppn_percent')
                        ->numeric()
                        ->default(11)
                        ->suffix('%')
                        ->reactive()
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set)),
                    Forms\Components\TextInput::make('ppn_amount')
                        ->numeric()
                        ->default(0)
                        ->disabled()
                        ->dehydrated()
                        ->prefix('IDR'),
                    Forms\Components\TextInput::make('grand_total')
                        ->numeric()
                        ->default(0)
                        ->disabled()
                        ->dehydrated()
                        ->prefix('IDR'),
                ])->columns(2),

            Forms\Components\Textarea::make('notes')
                ->columnSpanFull(),

            Section::make('PO Items')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Forms\Components\Select::make('material_id')
                                ->label('Material')
                                ->options(function (callable $get) {
                                    $supplierId = $get('../../supplier_id');
                                    if (! $supplierId) {
                                        return [];
                                    }

                                    return Material::where('supplier_id', $supplierId)
                                        ->pluck('name', 'id');
                                })
                                ->getOptionLabelFromRecordUsing(function ($record) {
                                    $companyId = CompanyContext::getCompanyId();
                                    $stock = InventoryStock::where('material_id', $record->id)
                                        ->where('company_id', $companyId)
                                        ->sum('quantity');

                                    return "[{$record->code}] {$record->name} (Stock: ".number_format($stock, 2)." {$record->unit})";
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $material = Material::find($state, ['*']);
                                    if ($material) {
                                        $set('unit', $material->unit);
                                        $set('unit_price', $material->price);
                                    }
                                }),
                            Forms\Components\TextInput::make('qty')
                                ->numeric()
                                ->default(1)
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $qty = floatval($state);
                                    $price = floatval($get('unit_price'));
                                    $set('total_price', $qty * $price);
                                }),
                            Forms\Components\TextInput::make('unit')
                                ->default('pcs')
                                ->disabled()
                                ->dehydrated(),
                            Forms\Components\TextInput::make('unit_price')
                                ->numeric()
                                ->default(0)
                                ->prefix('IDR')
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $price = floatval($state);
                                    $qty = floatval($get('qty'));
                                    $set('total_price', $qty * $price);
                                }),
                            Forms\Components\TextInput::make('total_price')
                                ->numeric()
                                ->default(0)
                                ->disabled()
                                ->dehydrated()
                                ->prefix('IDR'),
                            Forms\Components\TextInput::make('qty_received')
                                ->numeric()
                                ->default(0)
                                ->disabled()
                                ->dehydrated(),
                        ])
                        ->columns(3)
                        ->columnSpanFull()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $subtotal = 0;
                            foreach ($state as $item) {
                                $subtotal += floatval($item['total_price'] ?? 0);
                            }
                            $set('subtotal', $subtotal);

                            $ppnPct = floatval($get('ppn_percent') ?? 11);
                            $ppnAmount = $subtotal * ($ppnPct / 100);
                            $set('ppn_amount', $ppnAmount);

                            $set('grand_total', $subtotal + $ppnAmount);
                        }),
                ]),
        ]);
    }

    protected static function recalculateTotals(Get $get, Set $set): void
    {
        $subtotal = floatval($get('subtotal') ?? 0);
        $ppnPct = floatval($get('ppn_percent') ?? 11);
        $ppnAmount = $subtotal * ($ppnPct / 100);
        $set('ppn_amount', $ppnAmount);
        $set('grand_total', $subtotal + $ppnAmount);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('po_number')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('project.project_code')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('supplier.name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('po_date')->date()->sortable(),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'draft' => 'gray',
                    'ordered' => 'info',
                    'partial' => 'warning',
                    'received' => 'success',
                    'cancelled' => 'danger',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('grand_total')->numeric()->sortable(),
        ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'ordered' => 'Ordered',
                    'partial' => 'Partial',
                    'received' => 'Received',
                    'cancelled' => 'Cancelled',
                ]),
                SelectFilter::make('supplier_id')->relationship('supplier', 'name'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    Action::make('generateInvoice')
                        ->label('Generate Invoice')
                        ->icon('heroicon-o-document-text')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === 'ordered' || $record->status === 'received')
                        ->action(function ($record) {
                            InvoiceGeneratorService::generateFromPO($record);
                            Notification::make()
                                ->title('Purchase Invoice generated successfully!')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
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

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPoSuppliers::route('/'),
            'create' => Pages\CreatePoSupplier::route('/create'),
            'edit' => Pages\EditPoSupplier::route('/{record}/edit'),
        ];
    }
}
