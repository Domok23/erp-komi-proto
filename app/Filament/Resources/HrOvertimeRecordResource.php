<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HrOvertimeRecordResource\Pages;
use App\Models\HrEmployee;
use App\Models\HrOvertimeRecord;
use App\Services\CompanyContext;
use Filament\Actions\Action;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class HrOvertimeRecordResource extends Resource
{
    protected static ?string $model = HrOvertimeRecord::class;

    protected static ?string $navigationLabel = 'Overtime';

    protected static ?string $modelLabel = 'Overtime Record';

    protected static ?string $pluralModelLabel = 'Overtime Records';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Overtime Details')
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
                        ->required()
                        ->maxDate(now()->addDays(7)),
                    Forms\Components\TextInput::make('hours')
                        ->numeric()
                        ->step(0.5)
                        ->minValue(0.5)
                        ->required(),
                    Forms\Components\Textarea::make('reason')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                        ])
                        ->default('pending')
                        ->required(),
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
            Tables\Columns\TextColumn::make('hours')
                ->sortable(),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('reason')
                ->limit(50),
        ])
            ->defaultSort('date', 'desc')
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(fn ($record) => $record->update([
                        'status' => 'approved',
                        'updated_by' => Auth::id(),
                    ])),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(fn ($record) => $record->update([
                        'status' => 'rejected',
                        'updated_by' => Auth::id(),
                    ])),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('employee')
            ->whereHas('employee', fn (Builder $query) => $query->where('company_id', CompanyContext::getCompanyId()));
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-arrow-trending-up';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'HR';
    }

    public static function getNavigationSort(): ?int
    {
        return 7;
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
            'index' => Pages\ListHrOvertimeRecords::route('/'),
            'create' => Pages\CreateHrOvertimeRecord::route('/create'),
            'edit' => Pages\EditHrOvertimeRecord::route('/{record}/edit'),
        ];
    }
}
