<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CostingResource\Pages;
use App\Models\Costing;
use App\Models\Project;
use App\Services\CostingCalculatorService;
use App\Services\CostingTransitionService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CostingResource extends Resource
{
    protected static ?string $model = Costing::class;

    protected static ?string $navigationLabel = 'Costing';

    protected static ?string $modelLabel = 'Costing';

    protected static ?string $pluralModelLabel = 'Costings';

    public static function form(Schema $schema): Schema
    {
        $isLocked = fn (?Costing $record): bool => $record && $record->isLocked();

        return $schema->schema([
            Forms\Components\Select::make('project_id')
                ->relationship('project', 'project_code')
                ->searchable()
                ->preload()
                ->required()
                ->reactive()
                ->disabled($isLocked)
                ->afterStateUpdated(function ($state, callable $set, Get $get) {
                    $project = Project::find($state, ['*']);
                    if ($project) {
                        $set('design_id', $project->design_id);

                        // Auto-fill MP cost from config: Rp 33,000/unit × target_qty
                        $targetQty = max(1, (int) $project->target_qty);
                        $mpCost = CostingCalculatorService::calculateMpCost($targetQty);
                        $set('mp_cost', $mpCost);

                        // Auto-fill overhead & profit from config
                        $set('overhead_pct', CostingCalculatorService::getDefaultOverheadPct());
                        $set('profit_margin_pct', CostingCalculatorService::getDefaultProfitMarginPct());

                        // Auto-fill material cost from design if available
                        $design = $project->design;
                        if ($design) {
                            if ($design->estimated_material_cost > 0) {
                                $set('material_cost', $design->estimated_material_cost);
                            }
                        }

                        self::recalculate($get, $set);
                    }
                }),
            Forms\Components\Select::make('design_id')
                ->relationship('design', 'name')
                ->disabled()
                ->dehydrated()
                ->required(),
            Forms\Components\DatePicker::make('costing_date')
                ->default(now()->toDateString())
                ->required()
                ->disabled($isLocked),
            Forms\Components\TextInput::make('version')
                ->required()
                ->default('1.0')
                ->maxLength(50)
                ->disabled($isLocked),
            Forms\Components\Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'calculated' => 'Calculated',
                    'submitted' => 'Submitted',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ])
                ->required()
                ->default('draft')
                ->disabled(),

            // --- Cost breakdown ---
            Forms\Components\TextInput::make('material_cost')
                ->label('Material Cost')
                ->numeric()
                ->default(0)
                ->prefix('IDR')
                ->helperText('Auto-imported from BOM')
                ->disabled()
                ->dehydrated(),
            Forms\Components\TextInput::make('mp_cost')
                ->label('Manufacturing Cost (MP)')
                ->numeric()
                ->default((int) CostingCalculatorService::getMpRatePerUnit())
                ->prefix('IDR')
                ->step(1)
                ->hint('Default: IDR '.number_format(CostingCalculatorService::getMpRatePerUnit(), 0, ',', '.').'/unit')
                ->live(onBlur: true)
                ->disabled($isLocked)
                ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculate($get, $set)),
            Forms\Components\TextInput::make('overhead_pct')
                ->label('Overhead %')
                ->numeric()
                ->default(CostingCalculatorService::getDefaultOverheadPct())
                ->suffix('%')
                ->hint('Default: '.CostingCalculatorService::getDefaultOverheadPct().'%')
                ->live(onBlur: true)
                ->disabled($isLocked)
                ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculate($get, $set)),
            Forms\Components\TextInput::make('overhead_amount')
                ->label('Overhead Amount')
                ->numeric()
                ->default(0)
                ->prefix('IDR')
                ->disabled()
                ->dehydrated(),
            Forms\Components\TextInput::make('shipping_cost')
                ->numeric()
                ->default(0)
                ->prefix('IDR')
                ->live(onBlur: true)
                ->disabled($isLocked)
                ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculate($get, $set)),
            Forms\Components\TextInput::make('profit_margin_pct')
                ->label('Profit Margin %')
                ->numeric()
                ->default(20)
                ->suffix('%')
                ->live(onBlur: true)
                ->disabled($isLocked)
                ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculate($get, $set)),
            Forms\Components\TextInput::make('profit_margin_amount')
                ->label('Profit Margin Amount')
                ->numeric()
                ->default(0)
                ->prefix('IDR')
                ->disabled()
                ->dehydrated(),

            // --- Result ---
            Forms\Components\TextInput::make('landed_cost')
                ->numeric()
                ->default(0)
                ->prefix('IDR')
                ->disabled()
                ->dehydrated(),
            Forms\Components\TextInput::make('selling_price')
                ->label('Selling Price')
                ->numeric()
                ->default(0)
                ->prefix('IDR')
                ->disabled()
                ->dehydrated(),
            Forms\Components\TextInput::make('currency')
                ->default('IDR')
                ->maxLength(10)
                ->disabled($isLocked),

            // --- Notes & approval info ---
            Forms\Components\Textarea::make('notes')
                ->maxLength(65535)
                ->columnSpanFull()
                ->disabled($isLocked),
            Forms\Components\Placeholder::make('submitted_by_display')
                ->label('Submitted By')
                ->content(fn (?Costing $record): string => $record && $record->submittedByUser
                    ? $record->submittedByUser->name.' — '.($record->submitted_at?->format('d M Y H:i') ?? '-')
                    : '-'),
            Forms\Components\Placeholder::make('approved_by_display')
                ->label('Approved By')
                ->content(fn (?Costing $record): string => $record && $record->approvedByUser
                    ? $record->approvedByUser->name.' — '.($record->approved_at?->format('d M Y H:i') ?? '-')
                    : '-'),
            Forms\Components\Placeholder::make('rejected_by_display')
                ->label('Rejected By')
                ->content(fn (?Costing $record): string => $record && $record->rejectedByUser
                    ? $record->rejectedByUser->name.' — '.($record->rejected_at?->format('d M Y H:i') ?? '-')
                    : '-'),
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
                Tables\Columns\TextColumn::make('approvedByUser.name')
                    ->label('Approved/Rejected By')
                    ->sortable()
                    ->searchable()
                    ->placeholder('-'),
            ])
            ->defaultSort('id', 'desc')
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
                    ->label('Import BOM')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->visible(fn (Costing $record): bool => $record->isEditable())
                    ->action(function (Costing $record) {
                        if (! $record->project) {
                            Notification::make()
                                ->title('No project linked')
                                ->danger()
                                ->send();

                            return;
                        }

                        $result = CostingCalculatorService::calculateFromBOM($record->project);
                        $record->update(['material_cost' => $result['material_cost']]);
                        CostingCalculatorService::recalculateCosting($record);

                        Notification::make()
                            ->title('BOM Cost Imported: IDR '.number_format($result['material_cost'], 2))
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                EditAction::make()
                    ->visible(fn (Costing $record): bool => $record->isEditable()),
                Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function (Costing $record) {
                        $new = CostingTransitionService::duplicate($record);

                        Notification::make()
                            ->title("Duplicated to v{$new->version}")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                DeleteAction::make()
                    ->visible(fn (Costing $record): bool => $record->status === 'draft'),
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
