<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HrLeaveTypeResource\Pages;
use App\Models\HrLeaveType;
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
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class HrLeaveTypeResource extends Resource
{
    protected static ?string $model = HrLeaveType::class;

    protected static ?string $navigationLabel = 'Leave Types';

    protected static ?string $modelLabel = 'Leave Type';

    protected static ?string $pluralModelLabel = 'Leave Types';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Leave Type Details')
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
                    Forms\Components\TextInput::make('default_quota_days')
                        ->label('Default Quota Days')
                        ->numeric()
                        ->minValue(0),
                    Forms\Components\Toggle::make('requires_document')
                        ->default(false),
                    Forms\Components\Toggle::make('is_sick_type')
                        ->label('Sick Leave Type')
                        ->default(false),
                    Forms\Components\Toggle::make('is_active')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('code')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('name')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('default_quota_days')
                ->label('Default Quota Days')
                ->sortable(),
            Tables\Columns\IconColumn::make('requires_document')
                ->boolean(),
            Tables\Columns\IconColumn::make('is_active')
                ->boolean(),
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

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-calendar-days';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'HR';
    }

    public static function getNavigationSort(): ?int
    {
        return 5;
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
            'index' => Pages\ListHrLeaveTypes::route('/'),
            'create' => Pages\CreateHrLeaveType::route('/create'),
            'edit' => Pages\EditHrLeaveType::route('/{record}/edit'),
        ];
    }
}
