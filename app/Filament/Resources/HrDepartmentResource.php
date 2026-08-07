<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HrDepartmentResource\Pages;
use App\Models\HrDepartment;
use App\Models\HrPosition;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class HrDepartmentResource extends Resource
{
    protected static ?string $model = HrDepartment::class;

    protected static ?string $navigationLabel = 'Departments';

    protected static ?string $modelLabel = 'Department';

    protected static ?string $pluralModelLabel = 'Departments';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Department Details')
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
                    Forms\Components\Toggle::make('is_active')
                        ->default(true),
                ])
                ->columns(2),

            Section::make('Positions')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Select::make('positions')
                        ->label('Positions')
                        ->relationship(
                            name: 'positions',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query, ?HrDepartment $record) => $query
                                ->where('company_id', CompanyContext::getCompanyId())
                                ->where(fn (Builder $q) => $q->whereNull('department_id')->when($record, fn ($q2) => $q2->orWhere('department_id', $record->id)))
                        )
                        ->getOptionLabelFromRecordUsing(fn (HrPosition $record) => "{$record->name} ({$record->code})")
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->helperText('Select positions belonging to this department (shows orphan positions and currently assigned positions).')
                        ->createOptionForm([
                            Forms\Components\TextInput::make('code')
                                ->required()
                                ->unique(
                                    table: 'hr_positions',
                                    column: 'code',
                                    modifyRuleUsing: fn (Unique $rule) => $rule->where('company_id', CompanyContext::getCompanyId())
                                )
                                ->maxLength(50),
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Toggle::make('is_active')
                                ->default(true),
                        ])
                        ->createOptionUsing(function (array $data, ?HrDepartment $record) {
                            $data['company_id'] = CompanyContext::getCompanyId();
                            if ($record) {
                                $data['department_id'] = $record->id;
                            }

                            return HrPosition::create($data)->getKey();
                        }),
                ]),
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
            Tables\Columns\TextColumn::make('positions.name')
                ->label('Positions')
                ->badge()
                ->separator(', ')
                ->placeholder('None'),
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
        return 'heroicon-o-building-office-2';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'HR';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
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
            'index' => Pages\ListHrDepartments::route('/'),
            'create' => Pages\CreateHrDepartment::route('/create'),
            'edit' => Pages\EditHrDepartment::route('/{record}/edit'),
        ];
    }
}
