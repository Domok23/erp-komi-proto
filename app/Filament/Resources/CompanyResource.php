<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    public static function canAccess(): bool
    {
        return false;
    }

    public static function getNavigationLabel(): string
    {
        return 'Companies';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('code')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(50),
            TextInput::make('name')->required()->maxLength(255),
            Select::make('type')
                ->required()
                ->options([
                    'main' => 'Main Company',
                    'branch' => 'Branch',
                ]),
            Textarea::make('address'),
            TextInput::make('city')->maxLength(100),
            TextInput::make('phone')->tel()->maxLength(30),
            TextInput::make('email')->email()->maxLength(100),
            TextInput::make('npwp')->maxLength(30),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->sortable(),
            TextColumn::make('code')->sortable()->searchable(),
            TextColumn::make('name')->sortable()->searchable(),
            BadgeColumn::make('type')
                ->colors([
                    'primary' => 'main',
                    'warning' => 'branch',
                ])
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'main' => 'Main Company',
                    'branch' => 'Branch',
                    default => $state,
                }),
            TextColumn::make('city')->sortable(),
            IconColumn::make('is_active')->boolean(),
            TextColumn::make('created_at')->dateTime()->sortable(),
        ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'main' => 'Main Company',
                        'branch' => 'Branch',
                    ]),
                SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
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

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-building-office';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Master Data';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
