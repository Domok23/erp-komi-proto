<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialUomResource\Pages;
use App\Models\MaterialUom;
use App\Services\CompanyContext;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class MaterialUomResource extends Resource
{
    protected static ?string $model = MaterialUom::class;

    protected static ?string $navigationLabel = 'Material UOMs';

    protected static ?string $modelLabel = 'Material UOM';

    protected static ?string $pluralModelLabel = 'Material UOMs';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return 'Master Data';
    }

    public static function getNavigationParentItem(): ?string
    {
        return 'Material';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-scale';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('name')
                ->label('UOM Name')
                ->validationAttribute('UOM Name')
                ->required()
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule) => $rule->where('company_id', CompanyContext::getCompanyId()))
                ->maxLength(100),
            Forms\Components\TextInput::make('description')
                ->label('Description (Optional)')
                ->maxLength(255),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('description')->limit(50),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaterialUoms::route('/'),
        ];
    }
}
