<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MerchandisingResource\Pages;
use App\Models\Merchandising;
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

class MerchandisingResource extends Resource
{
    protected static ?string $model = Merchandising::class;



    protected static ?string $navigationLabel = 'Merchandising';
    protected static ?string $modelLabel = 'Merchandising';
    protected static ?string $pluralModelLabel = 'Merchandising';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
                        Forms\Components\Select::make('project_id')
                ->relationship('project', 'name')
                ->nullable(),
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
                    'planned' => 'Planned',
                    'in_progress' => 'In Progress',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ])
                ->default('planned'),
            Forms\Components\DatePicker::make('start_date'),
            Forms\Components\DatePicker::make('target_date'),
            Forms\Components\DatePicker::make('completed_at'),
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
            Tables\Columns\TextColumn::make('project.name')->searchable(),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'planned' => 'gray',
                    'in_progress' => 'info',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('start_date')->date(),
            Tables\Columns\TextColumn::make('target_date')->date(),
            Tables\Columns\TextColumn::make('completed_at')->dateTime(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('status')->options([
                    'planned' => 'Planned',
                    'in_progress' => 'In Progress',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ]),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }



    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-clipboard-document-list';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Pre-Production';
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMerchandisings::route('/'),
            'create' => Pages\CreateMerchandising::route('/create'),
            'edit' => Pages\EditMerchandising::route('/{record}/edit'),
        ];
    }
}
