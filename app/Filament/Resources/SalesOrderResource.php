<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesOrderResource\Pages;
use App\Models\Costing;
use App\Models\Project;
use App\Models\SalesOrder;
use App\Services\CodeGenerator;
use App\Services\InvoiceGeneratorService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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

class SalesOrderResource extends Resource
{
    protected static ?string $model = SalesOrder::class;

    protected static ?string $navigationLabel = 'Sales Orders';

    protected static ?string $modelLabel = 'Sales Order';

    protected static ?string $pluralModelLabel = 'Sales Orders';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Sales Order Details')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('so_number')
                        ->default(fn () => CodeGenerator::generateSONumber())
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->maxLength(50),
                    Forms\Components\Select::make('project_id')
                        ->relationship('project', 'project_code')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (! $state) {
                                return;
                            }
                            $project = Project::find($state, ['*']);
                            if ($project) {
                                $set('customer_id', $project->customer_id);
                            }
                        }),
                    Forms\Components\Select::make('costing_id')
                        ->relationship('costing', 'version')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $costing = Costing::find($state, ['*']);
                            if ($costing) {
                                $set('unit_price', $costing->selling_price);
                            }
                        }),
                    Forms\Components\Select::make('customer_id')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\DatePicker::make('order_date')
                        ->default(now()->toDateString())
                        ->required(),
                    Forms\Components\DatePicker::make('delivery_date'),
                    Forms\Components\Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'confirmed' => 'Confirmed',
                            'in_production' => 'In Production',
                            'shipped' => 'Shipped',
                            'delivered' => 'Delivered',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('draft')
                        ->required(),
                    Forms\Components\Textarea::make('notes')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Quantities & Unit Cost')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('quantity')
                        ->numeric()
                        ->default(0)
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set)),
                    Forms\Components\TextInput::make('unit_price')
                        ->numeric()
                        ->default(0)
                        ->prefix('IDR')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set)),
                ])->columns(2),

            Section::make('Financial Details')
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
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set)),
                    Forms\Components\TextInput::make('ppn_amount')
                        ->numeric()
                        ->default(0)
                        ->disabled()
                        ->dehydrated()
                        ->prefix('IDR'),
                    Forms\Components\TextInput::make('shipping_cost')
                        ->numeric()
                        ->default(0)
                        ->prefix('IDR')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set)),
                    Forms\Components\TextInput::make('grand_total')
                        ->numeric()
                        ->default(0)
                        ->disabled()
                        ->dehydrated()
                        ->prefix('IDR'),
                ])->columns(2),

            Section::make('Payment Terms')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('down_payment_pct')
                        ->numeric()
                        ->default(0)
                        ->suffix('%')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set)),
                    Forms\Components\TextInput::make('down_payment_amount')
                        ->numeric()
                        ->default(0)
                        ->disabled()
                        ->dehydrated()
                        ->prefix('IDR'),
                    Forms\Components\Select::make('payment_terms')
                        ->options([
                            'cod' => 'COD',
                            'dp_30' => 'DP 30%',
                            'dp_50' => 'DP 50%',
                            'net_15' => 'Net 15',
                            'net_30' => 'Net 30',
                            'net_60' => 'Net 60',
                        ]),
                    Forms\Components\TextInput::make('currency')
                        ->default('IDR')
                        ->maxLength(10),
                ])->columns(2),

            Section::make('Sales Order Items')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Forms\Components\TextInput::make('description')
                                ->required(),
                            Forms\Components\TextInput::make('quantity')
                                ->numeric()
                                ->default(1)
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $qty = floatval($state);
                                    $price = floatval($get('unit_price'));
                                    $set('total_price', $qty * $price);
                                }),
                            Forms\Components\TextInput::make('unit')
                                ->default('pcs')
                                ->required(),
                            Forms\Components\TextInput::make('unit_price')
                                ->numeric()
                                ->default(0)
                                ->prefix('IDR')
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $price = floatval($state);
                                    $qty = floatval($get('quantity'));
                                    $set('total_price', $qty * $price);
                                }),
                            Forms\Components\TextInput::make('total_price')
                                ->numeric()
                                ->default(0)
                                ->disabled()
                                ->dehydrated()
                                ->prefix('IDR'),
                        ])
                        ->columns(3)
                        ->columnSpanFull()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            // Sum up items to update main SO fields
                            $subtotal = 0;
                            foreach ($state as $item) {
                                $subtotal += floatval($item['total_price'] ?? 0);
                            }
                            $set('subtotal', $subtotal);

                            $ppnPct = floatval($get('ppn_percent') ?? 11);
                            $ppnAmount = $subtotal * ($ppnPct / 100);
                            $set('ppn_amount', $ppnAmount);

                            $shipping = floatval($get('shipping_cost') ?? 0);
                            $grandTotal = $subtotal + $ppnAmount + $shipping;
                            $set('grand_total', $grandTotal);

                            $dpPct = floatval($get('down_payment_pct') ?? 0);
                            $set('down_payment_amount', $grandTotal * ($dpPct / 100));
                        }),
                ]),
        ]);
    }

    protected static function recalculateTotals(Get $get, Set $set): void
    {
        $qty = floatval($get('quantity') ?? 0);
        $unitPrice = floatval($get('unit_price') ?? 0);
        $subtotal = $qty * $unitPrice;

        // If items are not set or empty, we use form values
        $set('subtotal', $subtotal);

        $ppnPct = floatval($get('ppn_percent') ?? 11);
        $ppnAmount = $subtotal * ($ppnPct / 100);
        $set('ppn_amount', $ppnAmount);

        $shipping = floatval($get('shipping_cost') ?? 0);
        $grandTotal = $subtotal + $ppnAmount + $shipping;
        $set('grand_total', $grandTotal);

        $dpPct = floatval($get('down_payment_pct') ?? 0);
        $set('down_payment_amount', $grandTotal * ($dpPct / 100));
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('so_number')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('project.project_code')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('customer.name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('order_date')->date()->sortable(),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'draft' => 'gray',
                    'confirmed' => 'info',
                    'in_production' => 'warning',
                    'shipped' => 'primary',
                    'delivered' => 'success',
                    'cancelled' => 'danger',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('grand_total')
                ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                ->sortable(),
            Tables\Columns\TextColumn::make('currency'),
        ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'confirmed' => 'Confirmed',
                    'in_production' => 'In Production',
                    'shipped' => 'Shipped',
                    'delivered' => 'Delivered',
                    'cancelled' => 'Cancelled',
                ]),
                SelectFilter::make('customer_id')->relationship('customer', 'name'),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('generateInvoice')
                        ->label('Generate Invoice')
                        ->icon('heroicon-o-document-text')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === 'confirmed' || $record->status === 'shipped')
                        ->action(function ($record) {
                            InvoiceGeneratorService::generateFromSO($record);
                            Notification::make()
                                ->title('Invoice generated successfully!')
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
        return 'heroicon-o-document-currency-dollar';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Sales & Shipping';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesOrders::route('/'),
            'create' => Pages\CreateSalesOrder::route('/create'),
            'edit' => Pages\EditSalesOrder::route('/{record}/edit'),
        ];
    }
}
