<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RdDesignResource\Pages;
use App\Filament\Resources\RdDesignResource\RelationManagers;
use App\Models\RdDesign;
use App\Services\CompanyContext;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Unique;

class RdDesignResource extends Resource
{
    protected static ?string $model = RdDesign::class;

    protected static ?string $navigationLabel = 'R&D Design';

    protected static ?string $modelLabel = 'R&D Design';

    protected static ?string $pluralModelLabel = 'R&D Designs';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'code', 'brand'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Design Details')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->required()
                        ->unique(
                            ignoreRecord: true,
                            modifyRuleUsing: fn (Unique $rule) => $rule->where('company_id', CompanyContext::getCompanyId())
                        )
                        ->maxLength(50),
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('version')
                        ->default('1.0')
                        ->required()
                        ->maxLength(20),
                    Forms\Components\Select::make('parent_design_id')
                        ->label('Parent Design Revision')
                        ->relationship(
                            'parentDesign',
                            'name',
                            modifyQueryUsing: fn (Builder $query, ?RdDesign $record) => $query
                                ->where('company_id', CompanyContext::getCompanyId())
                                ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                        )
                        ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} - {$record->name} (v{$record->version})")
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->placeholder('None (Original Design)')
                        ->disabled()
                        ->dehydrated()
                        ->hintAction(
                            Action::make('openParent')
                                ->icon('heroicon-m-arrow-top-right-on-square')
                                ->iconButton()
                                ->hiddenLabel()
                                ->tooltip('Open Parent Design')
                                ->color('primary')
                                ->visible(fn (?RdDesign $record) => filled($record?->parent_design_id))
                                ->url(fn (?RdDesign $record) => $record?->parent_design_id ? static::getUrl('edit', ['record' => $record->parent_design_id]) : null)
                                ->openUrlInNewTab()
                        ),
                    Forms\Components\Select::make('product_type')
                        ->label('Bag Type')
                        ->options(self::getProductTypeOptions())
                        ->default('backpack')
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'under_review' => 'Under Review',
                            'approved' => 'Approved',
                            'obsolete' => 'Obsolete',
                            'archived' => 'Archived',
                        ])
                        ->default('draft')
                        ->required(),
                    Forms\Components\TextInput::make('brand')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('size_range')
                        ->maxLength(255),
                    Forms\Components\FileUpload::make('reference_image')
                        ->directory('designs')
                        ->image(),
                    Forms\Components\FileUpload::make('tech_pack')
                        ->directory('techpacks'),
                    Forms\Components\Textarea::make('description')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('notes')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Cost Estimations (Read-Only)')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('estimated_material_cost')
                        ->numeric()
                        ->prefix('IDR')
                        ->disabled(),
                    Forms\Components\TextInput::make('estimated_mp_cost')
                        ->label(new HtmlString('Estimated Labor Cost <span title="Estimated direct labor cost per unit (default IDR 33,000)" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->numeric()
                        ->prefix('IDR')
                        ->disabled(),
                    Forms\Components\TextInput::make('estimated_overhead_pct')
                        ->label(new HtmlString('Estimated Overhead (%) <span title="Estimated indirect factory overhead allocation (default 15%)" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->numeric()
                        ->suffix('%')
                        ->disabled(),
                    Forms\Components\TextInput::make('estimated_profit_margin_pct')
                        ->label(new HtmlString('Estimated Profit Margin (%) <span title="Estimated target profit margin percentage per unit (default 20%)" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->numeric()
                        ->suffix('%')
                        ->disabled(),
                    Forms\Components\TextInput::make('estimated_selling_price')
                        ->numeric()
                        ->prefix('IDR')
                        ->disabled(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('code')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('version')
                ->badge()
                ->color('info')
                ->sortable(),
            Tables\Columns\TextColumn::make('product_type')
                ->label('Bag Type')
                ->formatStateUsing(fn (?string $state): string => self::getProductTypeOptions()[$state] ?? ucfirst(str_replace('_', ' ', $state ?? '-')))
                ->badge()
                ->color('gray')
                ->sortable(),
            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'approved' => 'success',
                    'under_review' => 'warning',
                    'obsolete', 'archived' => 'danger',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('brand'),
            Tables\Columns\TextColumn::make('size_range'),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'under_review' => 'Under Review',
                    'approved' => 'Approved',
                    'obsolete' => 'Obsolete',
                    'archived' => 'Archived',
                ]),
                SelectFilter::make('product_type')
                    ->label('Bag Type')
                    ->options(self::getProductTypeOptions()),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('createRevision')
                        ->label('New Revision')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('warning')
                        ->form([
                            Forms\Components\TextInput::make('new_version')
                                ->label('New Version Number')
                                ->default(fn (RdDesign $record) => sprintf('%.1f', ((float) ($record->version ?: '1.0')) + 0.1))
                                ->required(),
                        ])
                        ->action(function (RdDesign $record, array $data) {
                            $revision = $record->createRevision($data['new_version']);
                            Notification::make()
                                ->title('Revision Created')
                                ->body("Created revision v{$revision->version} for {$record->name}")
                                ->success()
                                ->send();
                        }),
                    Action::make('approveDesign')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (RdDesign $record) => $record->status !== 'approved')
                        ->requiresConfirmation()
                        ->action(function (RdDesign $record) {
                            $record->update(['status' => 'approved']);
                            Notification::make()
                                ->title('Design Approved')
                                ->body("{$record->name} (v{$record->version}) has been approved.")
                                ->success()
                                ->send();
                        }),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-light-bulb';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'R&D & Consumption';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ConsumptionRatesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRdDesigns::route('/'),
            'create' => Pages\CreateRdDesign::route('/create'),
            'edit' => Pages\EditRdDesign::route('/{record}/edit'),
        ];
    }

    public static function getProductTypeOptions(): array
    {
        return [
            'backpack' => 'Backpack',
            'handbag' => 'Handbag',
            'tote_bag' => 'Tote Bag',
            'messenger_bag' => 'Messenger Bag',
            'sports_bag' => 'Sports Bag',
            'duffel_bag' => 'Duffel Bag',
            'waist_bag' => 'Waist Bag',
            'clutch' => 'Clutch Bag',
            'other' => 'Other',
        ];
    }
}
