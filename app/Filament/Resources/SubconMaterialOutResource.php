<?php

namespace App\Filament\Resources;

use App\Filament\Actions\StockPreviewAction;
use App\Filament\Resources\SubconMaterialOutResource\Pages;
use App\Models\InventoryStock;
use App\Models\Material;
use App\Models\PoSubcon;
use App\Models\SubconMaterialOut;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class SubconMaterialOutResource extends Resource
{
    protected static ?string $model = SubconMaterialOut::class;

    protected static ?string $navigationLabel = 'Subcon Material Out';

    protected static ?string $modelLabel = 'Subcon Material Out';

    protected static ?string $pluralModelLabel = 'Subcon Material Outs';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Document Details')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('document_number')
                        ->required()
                        ->maxLength(50),
                    Forms\Components\Select::make('po_subcon_id')
                        ->relationship('poSubcon', 'po_number')
                        ->getOptionLabelFromRecordUsing(fn ($record) => new HtmlString('<a href="'.PoSubconResource::getUrl('edit', ['record' => $record]).'" class="ref-link">'.$record->po_number.'</a>'))
                        ->allowHtml()
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
                        }),
                    Forms\Components\Select::make('subcon_id')
                        ->relationship('subcon', 'name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => new HtmlString('<a href="'.SubconResource::getUrl('edit', ['record' => $record]).'" class="ref-link">'.$record->name.'</a>'))
                        ->allowHtml()
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\DatePicker::make('departure_date')
                        ->default(now()->toDateString())
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'sent' => 'Sent',
                            'received' => 'Received by Subcon',
                        ])
                        ->default('draft')
                        ->required(),
                    Forms\Components\Textarea::make('notes')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Delivery Details')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Select::make('delivery_method')
                        ->options([
                            'fleet' => 'Company Fleet',
                            'courier' => 'External Courier',
                        ])
                        ->nullable(),
                    Forms\Components\TextInput::make('courier_name')
                        ->label('Courier / Driver Name')
                        ->maxLength(255)
                        ->nullable(),
                    Forms\Components\TextInput::make('delivery_cost')
                        ->label('Delivery Cost')
                        ->numeric()
                        ->prefix('IDR')
                        ->step(0.01)
                        ->nullable(),
                    Forms\Components\DatePicker::make('estimated_arrival')
                        ->label('Estimated Arrival')
                        ->afterOrEqual('departure_date')
                        ->nullable(),
                ])
                ->columns(2),

            Section::make('Sent Materials')
                ->columnSpanFull()
                ->headerActions([
                    StockPreviewAction::make('form'),
                ])
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Forms\Components\Select::make('material_id')
                                ->relationship(
                                    'material',
                                    'name',
                                    fn ($query) => $query->whereHas('inventoryStocks', function ($q) {
                                        $companyId = CompanyContext::getCompanyId();
                                        $q->where('company_id', $companyId)
                                            ->where('quantity', '>', 0);
                                    })
                                )
                                ->getOptionLabelFromRecordUsing(fn ($record) => $record->formatted_select_label)
                                ->searchable(['code', 'name', 'color', 'size'])
                                ->preload()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $material = $state ? Material::with('uomRef')->find($state) : null;
                                    $uom = $material?->uom ?? $material?->uomRef?->name ?? $material?->unit;
                                    $set('unit', $uom);
                                }),
                            Forms\Components\TextInput::make('qty_sent')
                                ->numeric()
                                ->step(0.01)
                                ->default(1)
                                ->required()
                                ->minValue(0.01)
                                ->rules([
                                    fn ($get) => function (string $attribute, $value, $fail) use ($get) {
                                        $materialId = $get('material_id');
                                        if (! $materialId) {
                                            return;
                                        }
                                        $companyId = CompanyContext::getCompanyId();
                                        $stock = InventoryStock::where('material_id', $materialId)
                                            ->where('company_id', $companyId)
                                            ->sum('quantity');
                                        if (floatval($value) > $stock) {
                                            $fail("Insufficient warehouse stock. Current available stock: {$stock}.");
                                        }
                                    },
                                ]),
                            Forms\Components\TextInput::make('unit')
                                ->label('UOM')
                                ->disabled()
                                ->dehydrated(),
                        ])
                        ->columns(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['poSubcon', 'subcon']))
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('document_number')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('poSubcon.po_number')->label('Subcon PO'),
                Tables\Columns\TextColumn::make('subcon.name')->sortable(),
                Tables\Columns\TextColumn::make('departure_date')->date()->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'received' => 'Received by Subcon',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'sent' => 'info',
                        'received' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'sent' => 'Sent',
                    'received' => 'Received by Subcon',
                ]),
            ])
            ->actions([
                ActionGroup::make([
                    StockPreviewAction::make('table'),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-arrow-up-on-square';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Inventory & Subcon';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubconMaterialOuts::route('/'),
            'create' => Pages\CreateSubconMaterialOut::route('/create'),
            'edit' => Pages\EditSubconMaterialOut::route('/{record}/edit'),
        ];
    }
}
