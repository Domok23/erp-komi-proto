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
            Forms\Components\Select::make('design_id')
                ->relationship('design', 'name')
                ->nullable(),
            Forms\Components\TextInput::make('version')
                ->required()
                ->default('1.0')
                ->maxLength(50),
            Forms\Components\Select::make('status')
                ->options([
                    'preliminary' => 'Preliminary',
                    'tech_pack' => 'Tech Pack',
                    'finalised' => 'Finalised',
                    'cancelled' => 'Cancelled',
                ])
                ->default('preliminary')
                ->required(),
            Forms\Components\DatePicker::make('issued_date'),
            Forms\Components\TextInput::make('issued_by')
                ->maxLength(255),
            Forms\Components\KeyValue::make('materials_spec')
                ->columnSpanFull(),
            Forms\Components\KeyValue::make('colors')
                ->columnSpanFull(),
            Forms\Components\KeyValue::make('measurements')
                ->columnSpanFull(),
            Forms\Components\Textarea::make('special_instructions')
                ->maxLength(65535)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('project.name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('design.name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('version')->sortable(),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'preliminary' => 'gray',
                    'tech_pack' => 'info',
                    'finalised' => 'success',
                    'cancelled' => 'danger',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('issued_date')->date()->sortable(),
            Tables\Columns\TextColumn::make('issued_by')->searchable(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('status')->options([
                    'preliminary' => 'Preliminary',
                    'tech_pack' => 'Tech Pack',
                    'finalised' => 'Finalised',
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
