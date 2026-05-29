<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
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

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;



    protected static ?string $navigationLabel = 'Customers';
    protected static ?string $modelLabel = 'Customers';
    protected static ?string $pluralModelLabel = 'Customers';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
                        Forms\Components\TextInput::make('code')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(50),
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('contact_person')
                ->maxLength(255),
            Forms\Components\Textarea::make('address')
                ->maxLength(65535)
                ->columnSpanFull(),
            Forms\Components\TextInput::make('city')
                ->maxLength(100),
            Forms\Components\TextInput::make('country')
                ->maxLength(100),
            Forms\Components\TextInput::make('phone')
                ->tel()
                ->maxLength(30),
            Forms\Components\TextInput::make('email')
                ->email()
                ->maxLength(100),
            Forms\Components\TextInput::make('npwp')
                ->maxLength(30),
            Forms\Components\Select::make('payment_terms')
                ->options([
                    'cod' => 'COD',
                    'dp_30' => 'DP 30%',
                    'dp_50' => 'DP 50%',
                    'net_15' => 'Net 15',
                    'net_30' => 'Net 30',
                    'net_60' => 'Net 60',
                ]),
            Forms\Components\Toggle::make('is_active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')
                ->sortable(),
            Tables\Columns\TextColumn::make('code')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('name')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('contact_person')
                ->searchable(),
            Tables\Columns\TextColumn::make('city'),
            Tables\Columns\TextColumn::make('phone'),
            Tables\Columns\TextColumn::make('email')
                ->searchable(),
            Tables\Columns\IconColumn::make('is_active')
                ->boolean(),
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
                SelectFilter::make('company_id')
                    ->relationship('company', 'name'),
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
        return 'heroicon-o-user-group';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Master Data';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}