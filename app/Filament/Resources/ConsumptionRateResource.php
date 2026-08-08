<?php

namespace App\Filament\Resources;

use App\Filament\Actions\StockPreviewAction;
use App\Filament\Resources\ConsumptionRateResource\Pages;
use App\Models\ConsumptionRate;
use App\Models\InventoryStock;
use App\Models\Material;
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

class ConsumptionRateResource extends Resource
{
    protected static ?string $model = ConsumptionRate::class;

    protected static ?string $navigationLabel = 'Consumption Rates';

    protected static ?string $modelLabel = 'Consumption Rate';

    protected static ?string $pluralModelLabel = 'Consumption Rates';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Consumption Rate Details')
                ->columnSpanFull()
                ->headerActions([
                    StockPreviewAction::make('form'),
                ])
                ->schema([
                    Forms\Components\Select::make('design_id')
                        ->relationship('design', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
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
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $material = Material::find($state);
                            if ($material) {
                                $set('unit', $material->unit);
                            }
                        }),
                    Forms\Components\TextInput::make('standard_rate')
                        ->numeric()
                        ->step(0.01)
                        ->required()
                        ->label(new HtmlString('Standard Rate <span title="Jumlah bersih kebutuhan bahan per unit barang (tanpa wastage)" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>')),
                    Forms\Components\TextInput::make('unit')
                        ->default('pcs')
                        ->disabled()
                        ->dehydrated(),
                    Forms\Components\TextInput::make('wastage_rate')
                        ->numeric()
                        ->step(0.01)
                        ->default(0)
                        ->required()
                        ->label(new HtmlString('Wastage Rate <span title="Persentase toleransi sisa bahan yang terbuang/rusak saat produksi" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->suffix('%'),
                    Forms\Components\Select::make('component')
                        ->label('Component')
                        ->options(function ($state) {
                            $companyId = CompanyContext::getCompanyId();

                            $options = \App\Models\Component::where('company_id', $companyId)
                                ->pluck('name', 'name')
                                ->toArray();

                            if ($state && ! isset($options[$state])) {
                                $options[$state] = $state;
                            }

                            return $options;
                        })
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->label('Component Name')
                                ->required(),
                        ])
                        ->createOptionUsing(function (array $data): string {
                            $companyId = CompanyContext::getCompanyId();
                            $comp = \App\Models\Component::firstOrCreate([
                                'company_id' => $companyId,
                                'name' => trim($data['name']),
                            ]);

                            return $comp->name;
                        })
                        ->createOptionModalHeading('Add New Component')
                        ->nullable(),
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
            Tables\Columns\TextColumn::make('design.name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('material.name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('standard_rate')
                ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                ->sortable(),
            Tables\Columns\TextColumn::make('unit'),
            Tables\Columns\TextColumn::make('wastage_rate')
                ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
            Tables\Columns\TextColumn::make('component')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('design_id')->relationship('design', 'name'),
                SelectFilter::make('material_id')->relationship('material', 'name'),
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
        return 'heroicon-o-presentation-chart-line';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'R&D & Consumption';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsumptionRates::route('/'),
            'create' => Pages\CreateConsumptionRate::route('/create'),
            'edit' => Pages\EditConsumptionRate::route('/{record}/edit'),
        ];
    }
}
