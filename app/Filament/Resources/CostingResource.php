<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CostingResource\Pages;
use App\Models\Costing;
use App\Models\Project;
use App\Services\CostingCalculatorService;
use App\Services\CostingTransitionService;
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
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class CostingResource extends Resource
{
    protected static ?string $model = Costing::class;

    protected static ?string $navigationLabel = 'Costing';

    protected static ?string $modelLabel = 'Costing';

    protected static ?string $pluralModelLabel = 'Costings';

    protected static ?string $recordTitleAttribute = 'version';

    public static function form(Schema $schema): Schema
    {
        $isLocked = fn (?Costing $record): bool => $record && $record->isLocked();

        return $schema->schema([
            Section::make('Costing Details')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Select::make('project_id')
                        ->relationship(
                            name: 'project',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query, $get, $record) => $query->where(function ($q) use ($get, $record) {
                                $selectedId = $get('project_id') ?? $record?->project_id;
                                $q->whereNull('archived_at');
                                if ($selectedId) {
                                    $q->orWhere('projects.id', $selectedId);
                                }
                            })
                        )
                        ->getOptionLabelFromRecordUsing(fn ($record) => new HtmlString('<a href="'.ProjectResource::getUrl('edit', ['record' => $record]).'" class="ref-link">'.$record->name.'</a> <span class="project-code-prefix">['.$record->project_code.']</span>'))
                        ->allowHtml()
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive()
                        ->disabled($isLocked)
                        ->afterStateUpdated(function ($state, callable $set, $get) {
                            $project = Project::find($state, ['*']);
                            if ($project) {
                                $set('design_id', $project->design_id);

                                // Auto-fill MP cost from config per unit
                                $mpCost = CostingCalculatorService::getMpRatePerUnit();
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
                            } else {
                                $set('design_id', null);
                                $set('mp_cost', 0);
                                $set('overhead_pct', 0);
                                $set('profit_margin_pct', 0);
                                $set('material_cost', 0);
                                self::recalculate($get, $set);
                            }
                        }),
                    Forms\Components\Select::make('sub_project_id')
                        ->label('Sub-Project')
                        ->relationship('subProject', 'name', function ($query, $get) {
                            $projectId = $get('project_id');
                            if ($projectId) {
                                return $query->where('project_id', $projectId);
                            }

                            return $query;
                        })
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->reactive()
                        ->visible(function ($get) {
                            $projectId = $get('project_id');
                            if (! $projectId) {
                                return false;
                            }
                            $project = Project::find($projectId);

                            return $project && $project->hasSubProjects();
                        })
                        ->required(function ($get) {
                            $projectId = $get('project_id');
                            if (! $projectId) {
                                return false;
                            }
                            $project = Project::find($projectId);

                            return $project && $project->hasSubProjects();
                        })
                        ->disabled($isLocked),
                    Forms\Components\Select::make('design_id')
                        ->relationship('design', 'name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => new HtmlString('<a href="'.RdDesignResource::getUrl('edit', ['record' => $record]).'" class="ref-link">'.$record->name.'</a>'))
                        ->allowHtml()
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
                ])
                ->columns(2),

            Section::make('Cost Breakdown')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('material_cost')
                        ->label(new HtmlString('Material Cost <span title="Total biaya bahan baku (material) per unit produk yang diimpor otomatis dari BOM" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->default(0)
                        ->prefix('IDR')
                        ->helperText('Auto-imported from BOM')
                        ->disabled()
                        ->dehydrated()
                        ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', ',') : $state)
                        ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),
                    Forms\Components\TextInput::make('mp_cost')
                        ->label(new HtmlString('Manufacturing Cost (MP) <span title="Total biaya tenaga kerja langsung per unit produk (default Rp 33.000)" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->numeric()
                        ->default((int) CostingCalculatorService::getMpRatePerUnit())
                        ->prefix('IDR')
                        ->step(1)
                        ->hint('Default: IDR '.number_format(CostingCalculatorService::getMpRatePerUnit(), 0, '.', ',').'/unit')
                        ->live(onBlur: true)
                        ->disabled($isLocked)
                        ->afterStateUpdated(fn ($get, $set) => self::recalculate($get, $set)),
                    Forms\Components\TextInput::make('overhead_pct')
                        ->label(new HtmlString('Overhead % <span title="Persentase alokasi biaya operasional tidak langsung pabrik (default 15%)" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->numeric()
                        ->step(0.01)
                        ->minValue(0)
                        ->default(CostingCalculatorService::getDefaultOverheadPct())
                        ->suffix('%')
                        ->hint('Default: '.CostingCalculatorService::getDefaultOverheadPct().'%')
                        ->live(onBlur: true)
                        ->disabled($isLocked)
                        ->afterStateUpdated(fn ($get, $set) => self::recalculate($get, $set)),
                    Forms\Components\TextInput::make('overhead_amount')
                        ->label(new HtmlString('Overhead Amount <span title="Nilai nominal biaya overhead per unit: (Material Cost + MP Cost) x Overhead %" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->default(0)
                        ->prefix('IDR')
                        ->disabled()
                        ->dehydrated()
                        ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', ',') : $state)
                        ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),
                    Forms\Components\TextInput::make('shipping_cost')
                        ->label(new HtmlString('Shipping Cost <span title="Biaya logistik pengiriman satu unit produk ke tujuan pelanggan" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->numeric()
                        ->step(0.01)
                        ->minValue(0)
                        ->default(0)
                        ->prefix('IDR')
                        ->live(onBlur: true)
                        ->disabled($isLocked)
                        ->afterStateUpdated(fn ($get, $set) => self::recalculate($get, $set)),
                    Forms\Components\TextInput::make('profit_margin_pct')
                        ->label(new HtmlString('Profit Margin % <span title="Persentase target keuntungan bersih per unit produk (default 20%)" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->numeric()
                        ->step(0.01)
                        ->minValue(0)
                        ->default(20)
                        ->suffix('%')
                        ->live(onBlur: true)
                        ->disabled($isLocked)
                        ->afterStateUpdated(fn ($get, $set) => self::recalculate($get, $set)),
                    Forms\Components\TextInput::make('profit_margin_amount')
                        ->label(new HtmlString('Profit Margin Amount <span title="Nilai nominal target keuntungan per unit: Landed Cost x Profit Margin %" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->default(0)
                        ->prefix('IDR')
                        ->disabled()
                        ->dehydrated()
                        ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', ',') : $state)
                        ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),

                    // --- Result ---
                    Forms\Components\TextInput::make('landed_cost')
                        ->label(new HtmlString('Landed Cost <span title="Total biaya modal pokok (HPP) per unit produk: Material + MP + Overhead + Shipping" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->default(0)
                        ->prefix('IDR')
                        ->disabled()
                        ->dehydrated()
                        ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', ',') : $state)
                        ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),
                    Forms\Components\TextInput::make('selling_price')
                        ->label(new HtmlString('Selling Price <span title="Harga jual final per unit produk ke pelanggan: Landed Cost + Profit Margin Amount" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->default(0)
                        ->prefix('IDR')
                        ->disabled()
                        ->dehydrated()
                        ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', ',') : $state)
                        ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),
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
                ])
                ->columns(2),
        ]);
    }

    protected static function recalculate($get, $set): void
    {
        $materialCost = (float) str_replace(',', '', $get('material_cost') ?: 0);
        $mpCost = (float) str_replace(',', '', $get('mp_cost') ?: 0);
        $overheadPct = (float) str_replace(',', '', $get('overhead_pct') ?: 0);
        $shippingCost = (float) str_replace(',', '', $get('shipping_cost') ?: 0);
        $profitMarginPct = (float) str_replace(',', '', $get('profit_margin_pct') ?: 0);

        $overheadAmount = ($materialCost + $mpCost) * ($overheadPct / 100);
        $set('overhead_amount', number_format($overheadAmount, 2, '.', ','));

        $landedCost = $materialCost + $mpCost + $overheadAmount + $shippingCost;
        $set('landed_cost', number_format($landedCost, 2, '.', ','));

        $profitMarginAmount = $landedCost * ($profitMarginPct / 100);
        $set('profit_margin_amount', number_format($profitMarginAmount, 2, '.', ','));

        $sellingPrice = $landedCost + $profitMarginAmount;
        $set('selling_price', number_format($sellingPrice, 2, '.', ','));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('project.name')->label('Project')->sortable()->searchable(),
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
                Tables\Columns\TextColumn::make('material_cost')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->sortable(),
                Tables\Columns\TextColumn::make('selling_price')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->sortable(),
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
                SelectFilter::make('project_id')->relationship('project', 'name'),
            ])
            ->actions([
                ActionGroup::make([
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

                            $result = CostingCalculatorService::calculateFromBOM($record->project, $record->subProject);
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
                ]),
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
