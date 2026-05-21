<?php
namespace App\Filament\Resources\SupplierResource\RelationManagers;

use App\Models\Material;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;

class MaterialsRelationManager extends RelationManager
{
    protected static string $relationship = 'materials';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Forms\Components\TextInput::make('code')->required(),
            \Filament\Forms\Components\TextInput::make('name')->required(),
            \Filament\Forms\Components\Select::make('category')
                ->options([
                    'fabric' => 'Fabric',
                    'zipper' => 'Zipper',
                    'button' => 'Button',
                    'thread' => 'Thread',
                    'handle' => 'Handle',
                    'label' => 'Label',
                    'interlining' => 'Interlining',
                    'other' => 'Other',
                ]),
            \Filament\Forms\Components\TextInput::make('unit')->default('pcs'),
            \Filament\Forms\Components\Toggle::make('is_active')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->sortable(),
            TextColumn::make('code')->sortable()->searchable(),
            TextColumn::make('name')->sortable()->searchable(),
            BadgeColumn::make('category'),
            TextColumn::make('unit'),
            IconColumn::make('is_active')->boolean(),
        ])
            ->filters([])
            ->headerActions([
                \Filament\Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),
                \Filament\Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}