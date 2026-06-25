<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CostingResource\Pages;
use App\Models\Costing;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Filters\SelectFilter;

class CostingResource extends Resource
{
    protected static ?string $model = Costing::class;

    protected static ?string $navigationLabel = 'Costing';
    protected static ?string $modelLabel = 'Costing';
    protected static ?string $pluralModelLabel = 'Costings';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('project_id')
                ->relationship('project', 'project_code')
                ->searchable()
                ->preload()
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set, Get $get) {
                    $project = \App\Models\Project::find($state, ['*']);
                    if ($project) {
                        $set('design_id', $project->design_id);
                        
                        $design = $project->design;
                        if ($design) {
                            if ($design->estimated_material_cost > 0) {
                                $set('material_cost', $design->estimated_material_cost);
                            }
                            if ($design->estimated_mp_cost > 0) {
                                $set('mp_cost', $design->estimated_mp_cost);
                            }
                            if ($design->estimated_overhead_pct > 0) {
                                $set('overhead_pct', $design->estimated_overhead_pct);
                            }
                            if ($design->estimated_profit_margin_pct > 0) {
                                $set('profit_margin_pct', $design->estimated_profit_margin_pct);
                            }
                            
                            self::recalculate($get, $set);
                        }
                    }
                }),
            Forms\Components\Select::make('design_id')
                ->relationship('design', 'name')
                ->disabled()
                ->dehydrated()
                ->required(),
            Forms\Components\DatePicker::make('costing_date')
                ->default(now()->toDateString())
                ->required(),
            Forms\Components\TextInput::make('version')
                ->required()
                ->default('1.0')
                ->maxLength(50),
            Forms\Components\Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'calculated' => 'Calculated',
                    'submitted' => 'Submitted',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ])
                ->required()
                ->default('draft'),
            Forms\Components\TextInput::make('material_cost')
                ->label('Material Cost')
                ->numeric()
                ->default(0)
                ->prefix('IDR')
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculate($get, $set)),
            Forms\Components\TextInput::make('mp_cost')
                ->label('Manufacturing Cost (MP)')
                ->numeric()
                ->default(0)
                ->prefix('IDR')
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculate($get, $set)),
            Forms\Components\TextInput::make('overhead_pct')
                ->label('Overhead %')
                ->numeric()
                ->default(15)
                ->suffix('%')
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculate($get, $set)),
            Forms\Components\TextInput::make('overhead_amount')
                ->label('Overhead Amount')
                ->numeric()
                ->default(0)
                ->prefix('IDR')
                ->readOnly(),
            Forms\Components\TextInput::make('shipping_cost')
                ->numeric()
                ->default(0)
                ->prefix('IDR')
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculate($get, $set)),
            Forms\Components\TextInput::make('profit_margin_pct')
                ->label('Profit Margin %')
                ->numeric()
                ->default(20)
                ->suffix('%')
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculate($get, $set)),
            Forms\Components\TextInput::make('profit_margin_amount')
                ->label('Profit Margin Amount')
                ->numeric()
                ->default(0)
                ->prefix('IDR')
                ->readOnly(),
            Forms\Components\TextInput::make('landed_cost')
                ->numeric()
                ->default(0)
                ->prefix('IDR')
                ->readOnly(),
            Forms\Components\TextInput::make('selling_price')
                ->label('Selling Price')
                ->numeric()
                ->default(0)
                ->prefix('IDR')
                ->readOnly(),
            Forms\Components\TextInput::make('currency')
                ->default('IDR')
                ->maxLength(10),
            Forms\Components\Textarea::make('notes')
                ->maxLength(65535)
                ->columnSpanFull(),
            Forms\Components\TextInput::make('approved_by')
                ->maxLength(255),
            Forms\Components\DatePicker::make('approved_at'),
        ]);
    }

    protected static function recalculate(Get $get, Set $set): void
    {
        $materialCost = (float) $get('material_cost') ?: 0;
        $mpCost = (float) $get('mp_cost') ?: 0;
        $overheadPct = (float) $get('overhead_pct') ?: 0;
        $shippingCost = (float) $get('shipping_cost') ?: 0;
        $profitMarginPct = (float) $get('profit_margin_pct') ?: 0;

        $overheadAmount = ($materialCost + $mpCost) * ($overheadPct / 100);
        $set('overhead_amount', round($overheadAmount, 2));

        $landedCost = $materialCost + $mpCost + $overheadAmount + $shippingCost;
        $set('landed_cost', round($landedCost, 2));

        $profitMarginAmount = $landedCost * ($profitMarginPct / 100);
        $set('profit_margin_amount', round($profitMarginAmount, 2));

        $sellingPrice = $landedCost + $profitMarginAmount;
        $set('selling_price', round($sellingPrice, 2));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('project.project_code')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('version')->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'calculated' => 'info',
                        'submitted' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('material_cost')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('selling_price')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('currency')->sortable(),
                Tables\Columns\TextColumn::make('approved_by')->searchable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'calculated' => 'Calculated',
                    'submitted' => 'Submitted',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ]),
                SelectFilter::make('project_id')->relationship('project', 'project_code'),
            ])
            ->actions([
                Action::make('importFromBOM')
                    ->label('Import BOM Cost')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(function ($record) {
                        $result = \App\Services\CostingCalculatorService::calculateFromBOM($record->project);
                        $record->update([
                            'material_cost' => $result['material_cost'],
                        ]);
                        \App\Services\CostingCalculatorService::recalculateCosting($record);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('BOM Cost Imported: IDR ' . number_format($result['material_cost'], 2))
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                EditAction::make(),
                DeleteAction::make()
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-calculator';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Costing & Pricing';
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
            'index' => Pages\ListCostings::route('/'),
            'create' => Pages\CreateCosting::route('/create'),
            'edit' => Pages\EditCosting::route('/{record}/edit'),
        ];
    }
}