<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RdDesignResource\Pages;
use App\Models\RdDesign;
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
                ->maxLength(50),
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('description')
                ->maxLength(65535)
                ->columnSpanFull(),
            Forms\Components\TextInput::make('category')
                ->maxLength(100),
            Forms\Components\FileUpload::make('file_path')
                ->directory('designs'),
            Forms\Components\Select::make('status')
                ->options([
                    'concept' => 'Concept',
                    'in_progress' => 'In Progress',
                    'review' => 'Review',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ])
                ->default('concept'),
            Forms\Components\TextInput::make('designer')
                ->maxLength(255),
            Forms\Components\DatePicker::make('start_date'),
            Forms\Components\DatePicker::make('completion_date'),
            Forms\Components\Textarea::make('notes')
                ->maxLength(65535)
                ->columnSpanFull(),
            Forms\Components\Toggle::make('is_active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('code')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('category'),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'concept' => 'gray',
                    'in_progress' => 'info',
                    'review' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('designer'),
            Tables\Columns\TextColumn::make('start_date')->date(),
            Tables\Columns\TextColumn::make('completion_date')->date(),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('status')->options([
                    'concept' => 'Concept',
                    'in_progress' => 'In Progress',
                    'review' => 'Review',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ]),
                SelectFilter::make('is_active')->options(['1' => 'Active', '0' => 'Inactive']),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }



    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-light-bulb';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Pre-Production';
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRdDesigns::route('/'),
            'create' => Pages\CreateRdDesign::route('/create'),
            'edit' => Pages\EditRdDesign::route('/{record}/edit'),
        ];
    }
}
