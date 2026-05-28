<?php
namespace App\Filament\Resources\SupplierResource\RelationManagers;

use App\Models\Material;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class MaterialsRelationManager extends RelationManager
{
    protected static string $relationship = 'materials';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Forms\Components\TextInput::make('code')
                ->required()
                ->unique(ignoreRecord: true),
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
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}