<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductionOrderResource\Pages;
use App\Models\MerchandisePlanning;
use App\Models\ProductionOrder;
use App\Models\Project;
use App\Services\CodeGenerator;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductionOrderResource extends Resource
{
    protected static ?string $model = ProductionOrder::class;

    protected static ?string $navigationLabel = 'Production Orders';

    protected static ?string $modelLabel = 'Production Order';

    protected static ?string $pluralModelLabel = 'Production Orders';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Production Order Details')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Hidden::make('id')
                        ->default(fn ($record) => $record ? $record->id : null),
                    Forms\Components\TextInput::make('production_number')
                        ->disabled()
                        ->dehydrated()
                        ->default(fn () => CodeGenerator::generateProductionOrderNumber())
                        ->required()
                        ->maxLength(50),
                    Forms\Components\Select::make('project_id')
                        ->relationship('project', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateHydrated(function ($state, callable $set) {
                            self::loadProjectMaterials($state, $set);
                        })
                        ->afterStateUpdated(function ($state, callable $set) {
                            self::loadProjectMaterials($state, $set);
                        }),
                    Forms\Components\Select::make('merchandising_planning_id')
                        ->relationship('merchandisingPlanning', 'id')
                        ->searchable()
                        ->preload()
                        ->label('Merchandising Planning')
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $planning = MerchandisePlanning::find($state);
                                if ($planning) {
                                    $items = $planning->items;
                                    if ($items->isNotEmpty()) {
                                        $materials = [];
                                        foreach ($items as $item) {
                                            if (! $item->material_id) {
                                                continue;
                                            }

                                            $material = $item->material;
                                            $supplier = $item->supplier;
                                            $totalPrice = $item->planned_qty * $item->unit_price;

                                            $materials[] = [
                                                'is_selected' => true,
                                                'material_name' => $material ? $material->name : 'N/A',
                                                'supplier_name' => $supplier ? $supplier->name : 'N/A',
                                                'planned_qty' => $item->planned_qty,
                                                'unit' => $item->unit,
                                                'unit_price' => number_format($item->unit_price, 2, '.', ','),
                                                'total_price' => number_format($totalPrice, 2, '.', ','),
                                                'material_id' => $item->material_id,
                                                'merchandising_planning_item_id' => $item->id,
                                            ];
                                        }
                                        $set('materials', $materials);
                                    }
                                }
                            }
                        }),
                    Forms\Components\TextInput::make('planned_qty')
                        ->required()
                        ->numeric()
                        ->step(1)
                        ->minValue(1)
                        ->default(0),
                    Forms\Components\TextInput::make('completed_qty')
                        ->default(0)
                        ->disabled()
                        ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 0, '.', ',') : $state)
                        ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),
                    Forms\Components\Select::make('status')
                        ->options([
                            'planned' => 'Planned',
                            'in_progress' => 'In Progress',
                            'qc_passed' => 'QC Passed',
                            'qc_failed' => 'QC Failed',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('planned')
                        ->required(),
                    Forms\Components\DatePicker::make('start_date')
                        ->native(false),
                    Forms\Components\DatePicker::make('end_date')
                        ->native(false),
                    Forms\Components\Textarea::make('notes')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Forms\Components\Placeholder::make('no_materials')
                ->label('No materials selected')
                ->content('Select a merchandising planning to see materials')
                ->visible(fn (callable $get) => ! $get('merchandising_planning_id')),
            Forms\Components\Repeater::make('materials')
                ->label('Materials from Merchandising')
                ->schema([
                    Forms\Components\TextInput::make('material_name')
                        ->label('Material')
                        ->disabled(),
                    Forms\Components\TextInput::make('supplier_name')
                        ->label('Supplier')
                        ->disabled(),
                    Forms\Components\TextInput::make('planned_qty')
                        ->label('Planned Qty')
                        ->disabled()
                        ->dehydrated()
                        ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', ',') : $state)
                        ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),
                    Forms\Components\TextInput::make('unit')
                        ->label('Unit')
                        ->disabled()
                        ->dehydrated(),
                    Forms\Components\TextInput::make('unit_price')
                        ->label('Price')
                        ->disabled(),
                    Forms\Components\TextInput::make('total_price')
                        ->label('Total')
                        ->disabled(),
                    Forms\Components\Hidden::make('material_id'),
                    Forms\Components\Hidden::make('merchandising_planning_item_id'),
                    Forms\Components\Hidden::make('is_selected')->default(true),
                ])
                ->columns(6)
                ->itemLabel(fn (array $state): ?string => $state['material_name'] ?? null)
                ->reorderable(false)
                ->addable(false)
                ->deletable(false)
                ->default([])
                ->visible(fn (callable $get) => $get('merchandising_planning_id'))
                ->columnSpanFull()
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    $materials = [];
                    foreach ($state as $item) {
                        if ($item['is_selected']) {
                            $materials[] = [
                                'material_id' => $item['material_id'],
                                'merchandising_planning_item_id' => $item['merchandising_planning_item_id'],
                                'planned_qty' => $item['planned_qty'],
                                'unit' => $item['unit'],
                                'is_selected' => $item['is_selected'],
                            ];
                        }
                    }
                    $set('selected_materials', $materials);
                }),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('production_number')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('project.name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('planned_qty')
                ->numeric(decimalPlaces: 0, decimalSeparator: '.', thousandsSeparator: ','),
            Tables\Columns\TextColumn::make('completed_qty')
                ->numeric(decimalPlaces: 0, decimalSeparator: '.', thousandsSeparator: ','),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'planned' => 'gray',
                    'in_progress' => 'info',
                    'qc_passed' => 'success',
                    'qc_failed' => 'danger',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('start_date')->date(),
            Tables\Columns\TextColumn::make('end_date')->date(),
        ])
            ->filters([
                SelectFilter::make('status')->options([
                    'planned' => 'Planned',
                    'in_progress' => 'In Progress',
                    'qc_passed' => 'QC Passed',
                    'qc_failed' => 'QC Failed',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ]),
                SelectFilter::make('project_id')->relationship('project', 'name'),
            ])
            ->actions([
                Action::make('start_production')
                    ->label('Start Production')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'planned')
                    ->action(function ($record) {
                        $record->update(['status' => 'in_progress', 'start_date' => now()]);
                        Notification::make()
                            ->title('Production Started')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                Action::make('complete_production')
                    ->label('Complete Production')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'in_progress')
                    ->action(function ($record) {
                        $record->update(['status' => 'completed', 'end_date' => now()]);
                        Notification::make()
                            ->title('Production Completed')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-cog';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Production';
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
            'index' => Pages\ListProductionOrders::route('/'),
            'create' => Pages\CreateProductionOrder::route('/create'),
            'edit' => Pages\EditProductionOrder::route('/{record}/edit'),
        ];
    }

    public static function loadProjectMaterials($state, callable $set): void
    {
        if ($state) {
            $project = Project::find($state);
            $planning = $project?->merchandisePlannings()->latest()->first();
            if ($planning) {
                $set('merchandising_planning_id', $planning->id);

                $items = $planning->items;
                if ($items->isNotEmpty()) {
                    $materials = [];
                    foreach ($items as $item) {
                        if (! $item->material_id) {
                            continue;
                        }

                        $material = $item->material;
                        $supplier = $item->supplier;
                        $totalPrice = $item->planned_qty * $item->unit_price;

                        $materials[] = [
                            'is_selected' => true,
                            'material_name' => $material ? $material->name : 'N/A',
                            'supplier_name' => $supplier ? $supplier->name : 'N/A',
                            'planned_qty' => $item->planned_qty,
                            'unit' => $item->unit,
                            'unit_price' => number_format($item->unit_price, 2, '.', ','),
                            'total_price' => number_format($totalPrice, 2, '.', ','),
                            'material_id' => $item->material_id,
                            'merchandising_planning_item_id' => $item->id,
                        ];
                    }
                    $set('materials', $materials);

                    return;
                }
            }
        }
        $set('merchandising_planning_id', null);
        $set('materials', []);
    }
}
