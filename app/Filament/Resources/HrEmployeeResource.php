<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HrEmployeeResource\Pages;
use App\Filament\Resources\HrEmployeeResource\RelationManagers;
use App\Models\HrEmployee;
use App\Models\Project;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HrEmployeeResource extends Resource
{
    protected static ?string $model = HrEmployee::class;

    protected static ?string $navigationLabel = 'Employees';

    protected static ?string $modelLabel = 'Employee';

    protected static ?string $pluralModelLabel = 'Employees';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Employee Details')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('employee_number')
                        ->label('Employee Number')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(50),
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('nik')
                        ->label('NIK')
                        ->unique(ignoreRecord: true)
                        ->maxLength(32),
                    Forms\Components\TextInput::make('phone')
                        ->tel()
                        ->maxLength(30),
                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->maxLength(100),
                    Forms\Components\Textarea::make('address')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                    Forms\Components\Select::make('department_id')
                        ->relationship('department', 'name')
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('position_id')
                        ->relationship('position', 'name')
                        ->searchable()
                        ->preload(),
                    Forms\Components\DatePicker::make('join_date'),
                    Forms\Components\Select::make('status')
                        ->options([
                            'active' => 'Active',
                            'inactive' => 'Inactive',
                        ])
                        ->default('active')
                        ->required(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('employee_number')
                ->label('Employee Number')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('name')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('department.name')
                ->label('Department')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('position.name')
                ->label('Position')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('activePlacement.project.project_code')
                ->label('Project')
                ->sortable()
                ->searchable(),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'active' => 'success',
                    'inactive' => 'gray',
                    default => 'gray',
                }),
        ])
            ->filters([
                SelectFilter::make('project_id')
                    ->label('Project')
                    ->options(fn (): array => Project::query()->pluck('project_code', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, $projectId): Builder => $query->whereHas(
                            'placements',
                            fn (Builder $placementQuery): Builder => $placementQuery
                                ->where('project_id', $projectId)
                                ->where('status', 'active')
                        )
                    )),
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                ]),
            ]);
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-users';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'HR';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ContractsRelationManager::class,
            RelationManagers\PlacementsRelationManager::class,
            RelationManagers\LeaveBalancesRelationManager::class,
            RelationManagers\PositionHistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHrEmployees::route('/'),
            'create' => Pages\CreateHrEmployee::route('/create'),
            'edit' => Pages\EditHrEmployee::route('/{record}/edit'),
        ];
    }
}
