<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubconResource\Pages;
use App\Models\Subcon;
use App\Services\CompanyContext;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class SubconResource extends Resource
{
    protected static ?string $model = Subcon::class;

    protected static ?string $navigationLabel = 'Subcontractor';

    protected static ?string $modelLabel = 'Subcontractor';

    protected static ?string $pluralModelLabel = 'Subcontractor';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Subcontractor Details')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->required()
                        ->unique(
                            ignoreRecord: true,
                            modifyRuleUsing: fn (Unique $rule) => $rule->where('company_id', CompanyContext::getCompanyId())
                        )
                        ->maxLength(50),
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('service_type')
                        ->options([
                            'embroidery' => 'Embroidery',
                            'printing' => 'Printing',
                            'sewing' => 'Sewing',
                            'cutting' => 'Cutting',
                            'finishing' => 'Finishing',
                            'other' => 'Other',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('contact_person')
                        ->maxLength(255),
                    Forms\Components\Textarea::make('address')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('phone')
                        ->tel()
                        ->maxLength(30),
                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->maxLength(100),
                    Forms\Components\Toggle::make('is_active')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('code')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
            Tables\Columns\BadgeColumn::make('service_type')
                ->colors([
                    'primary' => 'sewing',
                    'warning' => 'printing',
                    'success' => 'embroidery',
                    'info' => 'cutting',
                    'danger' => 'finishing',
                    'gray' => 'other',
                ]),
            Tables\Columns\TextColumn::make('contact_person'),
            Tables\Columns\TextColumn::make('phone'),
            Tables\Columns\TextColumn::make('email')->searchable(),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('service_type')->options([
                    'embroidery' => 'Embroidery',
                    'printing' => 'Printing',
                    'sewing' => 'Sewing',
                    'cutting' => 'Cutting',
                    'finishing' => 'Finishing',
                    'other' => 'Other',
                ]),
                SelectFilter::make('is_active')->options(['1' => 'Active', '0' => 'Inactive']),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-wrench-screwdriver';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Master Data';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubcons::route('/'),
            'create' => Pages\CreateSubcon::route('/create'),
            'edit' => Pages\EditSubcon::route('/{record}/edit'),
        ];
    }
}
