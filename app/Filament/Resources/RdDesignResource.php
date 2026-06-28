<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RdDesignResource\Pages;
use App\Filament\Resources\RdDesignResource\RelationManagers;
use App\Models\RdDesign;
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

class RdDesignResource extends Resource
{
    protected static ?string $model = RdDesign::class;

    protected static ?string $navigationLabel = 'R&D Design';

    protected static ?string $modelLabel = 'R&D Design';

    protected static ?string $pluralModelLabel = 'R&D Designs';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('code')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(50),
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('bag_type')
                ->options([
                    'handbag' => 'Handbag',
                    'sports_bag' => 'Sports Bag',
                    'backpack' => 'Backpack',
                    'messenger' => 'Messenger Bag',
                    'tote' => 'Tote Bag',
                    'other' => 'Other',
                ])
                ->required(),
            Forms\Components\Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'approved' => 'Approved',
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
            Section::make('Cost Estimations (Read-Only)')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('estimated_material_cost')
                        ->numeric()
                        ->prefix('IDR')
                        ->disabled(),
                    Forms\Components\TextInput::make('estimated_mp_cost')
                        ->numeric()
                        ->prefix('IDR')
                        ->disabled(),
                    Forms\Components\TextInput::make('estimated_overhead_pct')
                        ->numeric()
                        ->suffix('%')
                        ->disabled(),
                    Forms\Components\TextInput::make('estimated_profit_margin_pct')
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
            Tables\Columns\TextColumn::make('bag_type')->sortable(),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'draft' => 'gray',
                    'approved' => 'success',
                    'archived' => 'warning',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('brand'),
            Tables\Columns\TextColumn::make('size_range'),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'approved' => 'Approved',
                    'archived' => 'Archived',
                ]),
                SelectFilter::make('bag_type')->options([
                    'handbag' => 'Handbag',
                    'sports_bag' => 'Sports Bag',
                    'backpack' => 'Backpack',
                    'messenger' => 'Messenger Bag',
                    'tote' => 'Tote Bag',
                    'other' => 'Other',
                ]),
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
}
