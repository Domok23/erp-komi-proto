<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PoSubconResource\Pages;
use App\Models\PoSubcon;
use App\Services\CodeGenerator;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Filters\SelectFilter;

class PoSubconResource extends Resource
{
    protected static ?string $model = PoSubcon::class;

    protected static ?string $navigationLabel = 'PO Subcons';
    protected static ?string $modelLabel = 'PO Subcon';
    protected static ?string $pluralModelLabel = 'PO Subcons';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('po_number')
                ->default(fn () => CodeGenerator::generatePOSubconNo())
                ->disabled()
                ->dehydrated()
                ->required(),
            Forms\Components\Select::make('project_id')
                ->relationship('project', 'project_code')
                ->searchable()
                ->preload()
                ->nullable(),
            Forms\Components\Select::make('subcon_id')
                ->relationship('subcon', 'name')
                ->searchable()
                ->preload()
                ->required(),
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
            
            Section::make('Subcon Costs')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('service_cost')
                        ->numeric()
                        ->default(0)
                        ->prefix('IDR')
                        ->reactive()
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set)),
                    Forms\Components\TextInput::make('shipping_cost')
                        ->numeric()
                        ->default(0)
                        ->prefix('IDR')
                        ->reactive()
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set)),
                    Forms\Components\TextInput::make('shipping_return_cost')
                        ->numeric()
                        ->default(0)
                        ->prefix('IDR')
                        ->reactive()
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set)),
                    Forms\Components\TextInput::make('total_cost')
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
                            Forms\Components\TextInput::make('description')
                                ->required(),
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
                        ])
                        ->columns(3)
                        ->columnSpanFull()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $subtotal = 0;
                            foreach ($state as $item) {
                                $subtotal += floatval($item['total_price'] ?? 0);
                            }
                            $set('service_cost', $subtotal);
                            
                            $shipping = floatval($get('shipping_cost') ?? 0);
                            $shippingReturn = floatval($get('shipping_return_cost') ?? 0);
                            $set('total_cost', $subtotal + $shipping + $shippingReturn);
                        }),
                ])
        ]);
    }

    protected static function recalculateTotals(Get $get, Set $set): void
    {
        $service = floatval($get('service_cost') ?? 0);
        $shipping = floatval($get('shipping_cost') ?? 0);
        $shippingReturn = floatval($get('shipping_return_cost') ?? 0);
        $set('total_cost', $service + $shipping + $shippingReturn);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('po_number')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('project.project_code')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('subcon.name')->sortable()->searchable(),
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
            Tables\Columns\TextColumn::make('total_cost')->numeric()->sortable(),
        ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'ordered' => 'Ordered',
                    'partial' => 'Partial',
                    'received' => 'Received',
                    'cancelled' => 'Cancelled',
                ]),
                SelectFilter::make('subcon_id')->relationship('subcon', 'name'),
            ])
            ->actions([
                Action::make('generateInvoice')
                    ->label('Generate Invoice')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'ordered' || $record->status === 'received')
                    ->action(function ($record) {
                        \App\Services\InvoiceGeneratorService::generateFromSubconPO($record);
                        \Filament\Notifications\Notification::make()
                            ->title('Subcon Invoice generated successfully!')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                EditAction::make(),
                DeleteAction::make()
            ])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-briefcase';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Procurement';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPoSubcons::route('/'),
            'create' => Pages\CreatePoSubcon::route('/create'),
            'edit' => Pages\EditPoSubcon::route('/{record}/edit'),
        ];
    }
}
