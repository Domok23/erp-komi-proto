<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JobOrderResource\Pages;
use App\Models\JobOrder;
use App\Models\MerchandisePlanning;
use App\Models\ProductionOrder;
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
use Illuminate\Support\HtmlString;

class JobOrderResource extends Resource
{
    protected static ?string $model = JobOrder::class;

    protected static ?string $navigationLabel = 'Job Orders';

    protected static ?string $modelLabel = 'Job Order';

    protected static ?string $pluralModelLabel = 'Job Orders';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Job Order Details')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Hidden::make('id')
                        ->default(fn ($record) => $record ? $record->id : null),
                    Forms\Components\TextInput::make('job_order_number')
                        ->disabled()
                        ->dehydrated()
                        ->default(fn () => CodeGenerator::generateJobOrderNumber())
                        ->required()
                        ->maxLength(50),
                    Forms\Components\Select::make('production_order_id')
                        ->relationship('productionOrder', 'production_number')
                        ->getOptionLabelFromRecordUsing(fn ($record) => new HtmlString('<a href="'.ProductionOrderResource::getUrl('edit', ['record' => $record]).'" class="ref-link">'.$record->production_number.'</a>'))
                        ->allowHtml()
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateHydrated(function ($state, callable $set) {
                            self::loadProductionOrderMaterials($state, $set);
                        })
                        ->afterStateUpdated(function ($state, callable $set) {
                            self::loadProductionOrderMaterials($state, $set);
                        }),
                    Forms\Components\Select::make('merchandising_planning_id')
                        ->relationship('merchandisingPlanning', 'id')
                        ->getOptionLabelFromRecordUsing(fn ($record) => new HtmlString('<a href="'.MerchandisePlanningResource::getUrl('edit', ['record' => $record]).'" class="ref-link">Planning #'.$record->id.'</a>'))
                        ->allowHtml()
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
                                                'unit_price' => $item->unit_price,
                                                'total_price' => $totalPrice,
                                                'material_id' => $item->material_id,
                                                'merchandising_planning_item_id' => $item->id,
                                            ];
                                        }
                                        $set('materials', $materials);
                                    }
                                }
                            }
                        }),
                    Forms\Components\Select::make('task_type')
                        ->options([
                            'cutting' => 'Cutting',
                            'sewing' => 'Sewing',
                            'finishing' => 'Finishing',
                            'qc' => 'Quality Control',
                            'packing' => 'Packing',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('planned_qty')
                        ->required()
                        ->numeric()
                        ->step(1)
                        ->default(0),
                    Forms\Components\TextInput::make('completed_qty')
                        ->default(0)
                        ->disabled()
                        ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 0, '.', ',') : $state)
                        ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),
                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'in_progress' => 'In Progress',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('pending')
                        ->required(),
                    Forms\Components\DatePicker::make('start_date')
                        ->native(false),
                    Forms\Components\DatePicker::make('end_date')
                        ->native(false),
                    Forms\Components\TextInput::make('assigned_to')
                        ->maxLength(255),
                ])
                ->columns(2),
            Forms\Components\Placeholder::make('no_materials')
                ->label('No materials selected')
                ->content('Select a merchandising planning to see materials')
                ->visible(fn (callable $get) => ! $get('merchandising_planning_id')),
            Forms\Components\Repeater::make('materials')
                ->label('Materials from Merchandising')
                ->schema([
                    Forms\Components\Checkbox::make('is_selected')
                        ->default(true)
                        ->label('Select this material'),
                    Forms\Components\TextInput::make('material_name')
                        ->label('Material')
                        ->disabled(),
                    Forms\Components\TextInput::make('supplier_name')
                        ->label('Supplier')
                        ->disabled(),
                    Forms\Components\TextInput::make('planned_qty')
                        ->label('Planned Qty')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(),
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
            Forms\Components\Textarea::make('notes')
                ->maxLength(65535)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('job_order_number')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('productionOrder.production_number')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('task_type')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'cutting' => 'info',
                    'sewing' => 'warning',
                    'finishing' => 'success',
                    'qc' => 'primary',
                    'packing' => 'gray',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('planned_qty')
                ->numeric(decimalPlaces: 0, decimalSeparator: '.', thousandsSeparator: ','),
            Tables\Columns\TextColumn::make('completed_qty')
                ->numeric(decimalPlaces: 0, decimalSeparator: '.', thousandsSeparator: ','),
            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'pending' => 'gray',
                    'in_progress' => 'info',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('assigned_to')->searchable(),
            Tables\Columns\TextColumn::make('start_date')->date(),
            Tables\Columns\TextColumn::make('end_date')->date(),
        ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'in_progress' => 'In Progress',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ]),
                SelectFilter::make('task_type')->options([
                    'cutting' => 'Cutting',
                    'sewing' => 'Sewing',
                    'finishing' => 'Finishing',
                    'qc' => 'Quality Control',
                    'packing' => 'Packing',
                ]),
                SelectFilter::make('production_order_id')->relationship('productionOrder', 'production_number'),
            ])
            ->actions([
                Action::make('start_task')
                    ->label('Start Task')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(function ($record) {
                        $record->update(['status' => 'in_progress', 'start_date' => now()]);
                        Notification::make()
                            ->title('Task Started')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                Action::make('complete_task')
                    ->label('Complete Task')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'in_progress')
                    ->action(function ($record) {
                        $record->update(['status' => 'completed', 'end_date' => now(), 'completed_qty' => $record->planned_qty]);
                        Notification::make()
                            ->title('Task Completed')
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
        return 'heroicon-o-clipboard-document-list';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Production';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJobOrders::route('/'),
            'create' => Pages\CreateJobOrder::route('/create'),
            'edit' => Pages\EditJobOrder::route('/{record}/edit'),
        ];
    }

    public static function loadProductionOrderMaterials($state, callable $set): void
    {
        if ($state) {
            $productionOrder = ProductionOrder::find($state);
            if ($productionOrder && $productionOrder->merchandisingPlanning) {
                $set('merchandising_planning_id', $productionOrder->merchandisingPlanning->id);
                $set('planned_qty', $productionOrder->planned_qty);

                $planning = $productionOrder->merchandisingPlanning;
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
                            'unit_price' => $item->unit_price,
                            'total_price' => $totalPrice,
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
