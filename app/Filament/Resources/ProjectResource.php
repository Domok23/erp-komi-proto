<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Filters\SelectFilter;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;



    protected static ?string $navigationLabel = 'Project';
    protected static ?string $modelLabel = 'Project';
    protected static ?string $pluralModelLabel = 'Projects';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('code')
                ->required()
                ->maxLength(50),
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('description')
                ->maxLength(65535)
                ->columnSpanFull(),
            Forms\Components\Select::make('type')
                ->options([
                    'proto' => 'Prototype',
                    'sample' => 'Sample',
                    'mass' => 'Mass Production',
                ]),
            Forms\Components\Select::make('status')
                ->options([
                    'planning' => 'Planning',
                    'development' => 'Development',
                    'sampling' => 'Sampling',
                    'approved' => 'Approved',
                    'production' => 'Production',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ])
                ->default('planning'),
            Forms\Components\Select::make('customer_id')
                ->relationship('customer', 'name')
                ->nullable(),
            Forms\Components\Select::make('sales_order_id')
                ->relationship('salesOrder', 'so_number')
                ->nullable(),
            Forms\Components\Select::make('design_id')
                ->relationship('design', 'name')
                ->nullable(),
            Forms\Components\DatePicker::make('start_date'),
            Forms\Components\DatePicker::make('target_date'),
            Forms\Components\DatePicker::make('completed_at'),
            Forms\Components\TextInput::make('target_qty')
                ->numeric()
                ->default(0),
            Forms\Components\TextInput::make('produced_qty')
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('code')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
            Tables\Columns\BadgeColumn::make('type')
                ->colors(['primary' => 'proto', 'info' => 'sample', 'success' => 'mass']),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'planning' => 'gray',
                    'development' => 'info',
                    'sampling' => 'warning',
                    'approved' => 'primary',
                    'production' => 'warning',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('customer.name')->searchable(),
            Tables\Columns\TextColumn::make('start_date')->date(),
            Tables\Columns\TextColumn::make('target_date')->date(),
            Tables\Columns\TextColumn::make('target_qty')->numeric(),
            Tables\Columns\TextColumn::make('produced_qty')->numeric(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('status')->options(['planning' => 'Planning', 'development' => 'Development', 'sampling' => 'Sampling', 'approved' => 'Approved', 'production' => 'Production', 'completed' => 'Completed', 'cancelled' => 'Cancelled']),
                SelectFilter::make('type')->options(['proto' => 'Prototype', 'sample' => 'Sample', 'mass' => 'Mass Production']),
                SelectFilter::make('customer_id')->relationship('customer', 'name'),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }



    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-folder';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Pre-Production';
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
