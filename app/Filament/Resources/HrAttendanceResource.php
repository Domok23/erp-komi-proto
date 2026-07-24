<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HrAttendanceResource\Pages;
use App\Models\HrAttendance;
use App\Models\HrEmployee;
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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HrAttendanceResource extends Resource
{
    protected static ?string $model = HrAttendance::class;

    protected static ?string $navigationLabel = 'Attendance';

    protected static ?string $modelLabel = 'Attendance';

    protected static ?string $pluralModelLabel = 'Attendance';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Attendance Details')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Select::make('employee_id')
                        ->label('Employee')
                        ->options(function (): array {
                            return HrEmployee::query()
                                ->where('company_id', CompanyContext::getCompanyId())
                                ->where('status', 'active')
                                ->get()
                                ->mapWithKeys(fn (HrEmployee $employee): array => [
                                    $employee->id => "{$employee->name} <span style=\"color: #6b7280; font-size: 0.875em;\">({$employee->employee_number})</span>",
                                ])
                                ->all();
                        })
                        ->allowHtml()
                        ->searchable()
                        ->required(),
                    Forms\Components\DatePicker::make('date')
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->options([
                            'present' => 'Present',
                            'sick' => 'Sick',
                            'permission' => 'Permission',
                            'leave' => 'Leave',
                            'alpha' => 'Alpha',
                            'half_day' => 'Half Day',
                        ])
                        ->required(),
                    Forms\Components\Textarea::make('notes')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('employee.name')
                ->label('Employee')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('date')
                ->date()
                ->sortable(),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'present' => 'success',
                    'sick' => 'warning',
                    'permission' => 'info',
                    'leave' => 'primary',
                    'alpha' => 'danger',
                    'half_day' => 'warning',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('notes')
                ->limit(50),
        ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('employee_id')
                    ->label('Employee')
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date)
                            )
                            ->when(
                                $data['to'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date)
                            );
                    }),
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
        return 'heroicon-o-clock';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'HR';
    }

    public static function getNavigationSort(): ?int
    {
        return 6;
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
            'index' => Pages\ListHrAttendances::route('/'),
            'create' => Pages\CreateHrAttendance::route('/create'),
            'edit' => Pages\EditHrAttendance::route('/{record}/edit'),
        ];
    }
}
