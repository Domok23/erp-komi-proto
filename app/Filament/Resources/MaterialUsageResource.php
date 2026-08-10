<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialUsageResource\Pages;
use App\Models\JobOrder;
use App\Models\MaterialUsage;
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

class MaterialUsageResource extends Resource
{
    protected static ?string $model = MaterialUsage::class;

    protected static ?string $navigationLabel = 'Material Usage';

    protected static ?string $modelLabel = 'Material Usage';

    protected static ?string $pluralModelLabel = 'Material Usage';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Usage Details')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Select::make('job_order_id')
                        ->relationship('jobOrder', 'job_order_number')
                        ->getOptionLabelFromRecordUsing(fn ($record) => new HtmlString('<a href="'.JobOrderResource::getUrl('edit', ['record' => $record]).'" class="ref-link">'.$record->job_order_number.'</a>'))
                        ->allowHtml()
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateHydrated(function ($state, callable $set) {
                            self::loadJobOrderMaterials($state, $set);
                        })
                        ->afterStateUpdated(function ($state, callable $set) {
                            self::loadJobOrderMaterials($state, $set);
                        }),
                    Forms\Components\DatePicker::make('usage_date')
                        ->native(false)
                        ->default(now())
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->options([
                            'planned' => 'Planned',
                            'in_progress' => 'In Progress',
                            'completed' => 'Completed',
                        ])
                        ->default('planned')
                        ->required(),
                    Forms\Components\Textarea::make('notes')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Forms\Components\Placeholder::make('no_materials')
                ->label('No materials selected')
                ->content('Select a job order to see materials')
                ->visible(fn (callable $get) => ! $get('job_order_id')),
            Forms\Components\Repeater::make('materials')
                ->label('Materials')
                ->schema([
                    Forms\Components\TextInput::make('material_name')
                        ->label('Material')
                        ->disabled(),
                    Forms\Components\TextInput::make('planned_qty')
                        ->label('Planned Qty')
                        ->disabled()
                        ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', ',') : $state)
                        ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),
                    Forms\Components\TextInput::make('unit')
                        ->label('UOM')
                        ->disabled(),
                    Forms\Components\TextInput::make('unit_price')
                        ->label('Unit Price')
                        ->disabled()
                        ->default(0)
                        ->prefix('IDR')
                        ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', ',') : '0.00')
                        ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),
                    Forms\Components\TextInput::make('actual_qty')
                        ->label('Actual Qty')
                        ->numeric()
                        ->step(0.01)
                        ->default(0)
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                            $plannedQty = (float) str_replace(',', '', $get('planned_qty') ?? 0);
                            $actualQty = (float) ($state ?? 0);
                            $wasteQty = max(0, $plannedQty - $actualQty);
                            $set('waste_qty', number_format($wasteQty, 2, '.', ','));

                            $unitPrice = (float) str_replace(',', '', $get('unit_price') ?? 0);
                            $totalCost = $actualQty * $unitPrice;
                            $set('total_cost', number_format($totalCost, 2, '.', ','));
                        }),
                    Forms\Components\TextInput::make('waste_qty')
                        ->label('Waste Qty')
                        ->disabled()
                        ->default(0)
                        ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', ',') : $state)
                        ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),
                    Forms\Components\TextInput::make('total_cost')
                        ->label('Total Cost')
                        ->disabled()
                        ->default(0)
                        ->prefix('IDR')
                        ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', ',') : '0.00')
                        ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),
                    Forms\Components\Hidden::make('material_id'),
                    Forms\Components\Hidden::make('merchandising_planning_item_id'),
                ])
                ->columns(7)
                ->itemLabel(fn (array $state): ?string => $state['material_name'] ?? null)
                ->reorderable(false)
                ->addable(false)
                ->deletable(false)
                ->default([])
                ->visible(fn (callable $get) => $get('job_order_id'))
                ->columnSpanFull()
                ->reactive(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('jobOrder.job_order_number')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('material.name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('usage_date')->date(),
            Tables\Columns\TextColumn::make('planned_qty')
                ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
            Tables\Columns\TextColumn::make('actual_qty')
                ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
            Tables\Columns\TextColumn::make('waste_qty')
                ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
            Tables\Columns\TextColumn::make('unit')->label('UOM'),
            Tables\Columns\TextColumn::make('unit_price')
                ->money('IDR')
                ->sortable(),
            Tables\Columns\TextColumn::make('total_cost')
                ->money('IDR')
                ->sortable()
                ->label('Total Cost'),
            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'planned' => 'gray',
                    'in_progress' => 'info',
                    'completed' => 'success',
                    default => 'gray',
                }),
        ])
            ->filters([
                SelectFilter::make('status')->options([
                    'planned' => 'Planned',
                    'in_progress' => 'In Progress',
                    'completed' => 'Completed',
                ]),
                SelectFilter::make('job_order_id')->relationship('jobOrder', 'job_order_number'),
                SelectFilter::make('material_id')->relationship('material', 'name'),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-chart-bar';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Production';
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
            'index' => Pages\ListMaterialUsages::route('/'),
            'create' => Pages\CreateMaterialUsage::route('/create'),
            'edit' => Pages\EditMaterialUsage::route('/{record}/edit'),
        ];
    }

    public static function loadJobOrderMaterials($state, callable $set): void
    {
        if ($state) {
            $jobOrder = JobOrder::with('materials.material', 'materials.merchandisingPlanningItem')->find($state);
            if ($jobOrder) {
                $jobOrderMaterials = $jobOrder->materials;
                if ($jobOrderMaterials->isNotEmpty()) {
                    $materials = [];
                    foreach ($jobOrderMaterials as $jobOrderMaterial) {
                        $material = $jobOrderMaterial->material;
                        $merchandisingItem = $jobOrderMaterial->merchandisingPlanningItem;

                        $unitPrice = 0;
                        if ($merchandisingItem && $merchandisingItem->unit_price) {
                            $unitPrice = (float) $merchandisingItem->unit_price;
                        }

                        $plannedQty = (float) $jobOrderMaterial->planned_qty;
                        $totalCost = $plannedQty * $unitPrice;

                        $materials[] = [
                            'material_name' => $material ? $material->name : 'N/A',
                            'planned_qty' => $plannedQty,
                            'unit' => $jobOrderMaterial->unit ?? 'pcs',
                            'unit_price' => $unitPrice,
                            'total_cost' => $totalCost,
                            'actual_qty' => 0,
                            'waste_qty' => $plannedQty,
                            'material_id' => $jobOrderMaterial->material_id,
                            'merchandising_planning_item_id' => $jobOrderMaterial->merchandising_planning_item_id,
                        ];
                    }
                    $set('materials', $materials);

                    return;
                }
            }
        }
        $set('materials', []);
    }
}
