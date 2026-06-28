<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialLeftoverResource\Pages;
use App\Models\MaterialLeftover;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Filters\SelectFilter;

class MaterialLeftoverResource extends Resource
{
    protected static ?string $model = MaterialLeftover::class;

    protected static ?string $navigationLabel = 'Material Leftovers';
    protected static ?string $modelLabel = 'Material Leftover';
    protected static ?string $pluralModelLabel = 'Material Leftovers';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('job_order_id')
                ->relationship('jobOrder', 'job_order_number')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('material_id')
                ->relationship('material', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\DatePicker::make('leftover_date')
                ->native(false)
                ->default(now())
                ->required(),
            Forms\Components\TextInput::make('qty')
                ->numeric()
                ->default(0)
                ->required(),
            Forms\Components\TextInput::make('unit')
                ->default('pcs'),
            Forms\Components\Select::make('condition')
                ->options([
                    'usable' => 'Usable',
                    'damaged' => 'Damaged',
                    'scrap' => 'Scrap',
                ])
                ->default('usable')
                ->required(),
            Forms\Components\Select::make('status')
                ->options([
                    'available' => 'Available',
                    'reserved' => 'Reserved',
                    'disposed' => 'Disposed',
                ])
                ->default('available')
                ->required(),
            Forms\Components\Textarea::make('notes')
                ->maxLength(65535)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('jobOrder.job_order_number')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('material.name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('leftover_date')->date(),
            Tables\Columns\TextColumn::make('qty')->numeric(),
            Tables\Columns\TextColumn::make('unit'),
            Tables\Columns\BadgeColumn::make('condition')
                ->color(fn (string $state): string => match ($state) {
                    'usable' => 'success',
                    'damaged' => 'warning',
                    'scrap' => 'danger',
                    default => 'gray',
                }),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'available' => 'success',
                    'reserved' => 'info',
                    'disposed' => 'gray',
                    default => 'gray',
                }),
        ])
            ->filters([
                SelectFilter::make('condition')->options([
                    'usable' => 'Usable',
                    'damaged' => 'Damaged',
                    'scrap' => 'Scrap',
                ]),
                SelectFilter::make('status')->options([
                    'available' => 'Available',
                    'reserved' => 'Reserved',
                    'disposed' => 'Disposed',
                ]),
                SelectFilter::make('job_order_id')->relationship('jobOrder', 'job_order_number'),
                SelectFilter::make('material_id')->relationship('material', 'name'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
            ])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-inbox';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Production';
    }

    public static function getNavigationSort(): ?int
    {
        return 6;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaterialLeftovers::route('/'),
            'create' => Pages\CreateMaterialLeftover::route('/create'),
            'edit' => Pages\EditMaterialLeftover::route('/{record}/edit'),
        ];
    }
}
