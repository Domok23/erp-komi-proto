<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectBomResource\Pages;
use App\Models\ProjectBom;
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

class ProjectBomResource extends Resource
{
    protected static ?string $model = ProjectBom::class;



    protected static ?string $navigationLabel = 'Project BOM';
    protected static ?string $modelLabel = 'Project BOM';
    protected static ?string $pluralModelLabel = 'Project BOMs';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('company_id')
                ->relationship('company', 'name')
                ->required(),
            Forms\Components\Select::make('project_id')
                ->relationship('project', 'name')
                ->required(),
            Forms\Components\TextInput::make('code')
                ->required()
                ->maxLength(50),
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('description')
                ->maxLength(65535)
                ->columnSpanFull(),
            Forms\Components\Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'active' => 'Active',
                    'archived' => 'Archived',
                ])
                ->default('draft'),
            Forms\Components\DatePicker::make('effective_date'),
            Forms\Components\DatePicker::make('expires_at'),
            Forms\Components\Textarea::make('notes')
                ->maxLength(65535)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('code')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('project.code')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('project.name')->searchable(),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'draft' => 'gray',
                    'active' => 'success',
                    'archived' => 'warning',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('effective_date')->date(),
            Tables\Columns\TextColumn::make('expires_at')->date(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('status')->options(['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived']),
                SelectFilter::make('project_id')->relationship('project', 'name'),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }



    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Pre-Production';
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjectBoms::route('/'),
            'create' => Pages\CreateProjectBom::route('/create'),
            'edit' => Pages\EditProjectBom::route('/{record}/edit'),
        ];
    }
}
