<?php

namespace App\Filament\Resources\SupplierResource\RelationManagers;

use App\Services\CompanyContext;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class MaterialsRelationManager extends RelationManager
{
    protected static string $relationship = 'materials';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('code')
                ->required()
                ->maxLength(50)
                ->unique(
                    table: 'materials',
                    column: 'code',
                    ignorable: fn ($record) => $record,
                    modifyRuleUsing: fn (Unique $rule) => $rule->where('company_id', CompanyContext::getCompanyId())
                ),
            TextInput::make('name')->required(),
            Select::make('category_id')
                ->label('Category')
                ->relationship('categoryRef', 'name')
                ->searchable()
                ->preload(),
            Select::make('uom_id')
                ->label('UOM')
                ->relationship('uomRef', 'name')
                ->searchable()
                ->preload(),
            Toggle::make('is_active')
                ->default(true)
                ->inline(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['categoryRef', 'uomRef']))
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('code')->sortable()->searchable(),
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('categoryRef.name')->label('Category'),
                TextColumn::make('uomRef.name')->label('UOM'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
