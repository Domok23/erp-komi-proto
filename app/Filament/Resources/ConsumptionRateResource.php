<?php

namespace App\Filament\Resources;

use App\Filament\Actions\StockPreviewAction;
use App\Filament\Resources\ConsumptionRateResource\Pages;
use App\Models\Component;
use App\Models\ConsumptionRate;
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
                    StockPreviewAction::make('form', allowReserve: false),
                ])
                ->schema([
                    Forms\Components\Select::make('design_id')
                        ->relationship('design', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('material_id')
                        ->relationship('material', 'name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => new HtmlString('<a href="'.MaterialResource::getUrl('edit', ['record' => $record]).'" class="ref-link">'.$record->formatted_select_label.'</a>'))
                        ->allowHtml()
                        ->searchable(['code', 'name', 'color', 'size'])
                        ->preload()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $material = $state ? Material::with('uomRef')->find($state) : null;
                            if ($material) {
                                $set('unit', $material->uom ?? $material->uomRef?->name ?? $material->unit);
                            }
                        }),
                    Forms\Components\TextInput::make('standard_rate')
                        ->numeric()
                        ->step(0.01)
                        ->required()
                        ->label(new HtmlString('Actual Consumption <span title="Actual net material requirement per unit (without waste)" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>')),
                    Forms\Components\TextInput::make('unit')
                        ->label('UOM')
                        ->disabled()
                        ->dehydrated(),
                    Forms\Components\TextInput::make('wastage_rate')
                        ->default(config('costing.wastage_pct', 3))
                        ->disabled()
                        ->dehydrated()
                        ->label(new HtmlString('Yield 3% waste <span title="Production waste tolerance percentage (Fixed global 3%)" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->suffix('%'),
                    Forms\Components\Select::make('component')
                        ->label('Component')
                        ->options(function ($state) {
                            $companyId = CompanyContext::getCompanyId();

                            $options = Component::where('company_id', $companyId)
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
                            $comp = Component::firstOrCreate([
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
            Tables\Columns\TextColumn::make('material.name')
                ->sortable()
                ->searchable()
                ->html()
                ->formatStateUsing(function ($state, ConsumptionRate $record) {
                    if (! $state || ! $record->material_id) {
                        return $state ?? 'N/A';
                    }
                    $url = MaterialResource::getUrl('edit', ['record' => $record->material_id]);
                    $tooltip = $record->material?->code ? 'Code: '.$record->material->code : '';

                    return '<a href="'.$url.'" title="'.e($tooltip).'" class="hover:underline text-primary-600 dark:text-primary-400 font-medium cursor-pointer" onclick="event.stopPropagation()">'.e($state).'</a>';
                })
                ->tooltip(fn (ConsumptionRate $record) => $record->material?->code ? 'Code: '.$record->material->code : null),
            Tables\Columns\TextColumn::make('unit')->label('UOM'),
            Tables\Columns\TextColumn::make('standard_rate')
                ->label('Actual Cons.')
                ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                ->sortable(),
            Tables\Columns\TextColumn::make('wastage_rate')
                ->label('Yield 3% waste')
                ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                ->suffix('%'),
            Tables\Columns\TextColumn::make('component')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('design_id')->relationship('design', 'name'),
                SelectFilter::make('material_id')
                    ->relationship('material', 'name')
                    ->searchable(['code', 'name', 'color', 'size'])
                    ->preload(),
            ])
            ->actions([
                ActionGroup::make([
                    StockPreviewAction::make('table', allowReserve: false),
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
