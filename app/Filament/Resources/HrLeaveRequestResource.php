<?php

namespace App\Filament\Resources;

use App\Exceptions\HrLeaveRequestException;
use App\Filament\Resources\HrLeaveRequestResource\Pages;
use App\Models\HrEmployee;
use App\Models\HrLeaveRequest;
use App\Services\LeaveRequestService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class HrLeaveRequestResource extends Resource
{
    protected static ?string $model = HrLeaveRequest::class;

    protected static ?string $navigationLabel = 'Leave Requests';

    protected static ?string $modelLabel = 'Leave Request';

    protected static ?string $pluralModelLabel = 'Leave Requests';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Leave Request Details')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Select::make('employee_id')
                        ->label('Employee')
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search): array {
                            return HrEmployee::query()
                                ->where(function (Builder $query) use ($search): void {
                                    $query->where('name', 'like', "%{$search}%")
                                        ->orWhere('employee_number', 'like', "%{$search}%");
                                })
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn (HrEmployee $employee): array => [
                                    $employee->id => "{$employee->name} ({$employee->employee_number})",
                                ])
                                ->all();
                        })
                        ->getOptionLabelUsing(function ($value): ?string {
                            $employee = HrEmployee::find($value);

                            return $employee ? "{$employee->name} ({$employee->employee_number})" : null;
                        })
                        ->required(),
                    Forms\Components\Select::make('leave_type_id')
                        ->label('Leave Type')
                        ->relationship('leaveType', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\DatePicker::make('start_date')
                        ->required(),
                    Forms\Components\DatePicker::make('end_date')
                        ->required(),
                    Forms\Components\Textarea::make('reason')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                    Forms\Components\FileUpload::make('file_path')
                        ->directory('hr/leaves'),
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
            Tables\Columns\TextColumn::make('leaveType.name')
                ->label('Leave Type')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('start_date')
                ->date()
                ->sortable(),
            Tables\Columns\TextColumn::make('end_date')
                ->date()
                ->sortable(),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    default => 'gray',
                }),
            Tables\Columns\BadgeColumn::make('source')
                ->color(fn (string $state): string => match ($state) {
                    'hrd' => 'primary',
                    'public_intake' => 'gray',
                    default => 'gray',
                }),
        ])
            ->defaultSort('start_date', 'desc')
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        try {
                            LeaveRequestService::approve($record, Auth::id());

                            Notification::make()
                                ->title('Leave approved')
                                ->success()
                                ->send();
                        } catch (HrLeaveRequestException $e) {
                            Notification::make()
                                ->title('Approval failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('rejected_reason')
                            ->label('Rejection Reason')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            LeaveRequestService::reject($record, $data['rejected_reason'], Auth::id());

                            Notification::make()
                                ->title('Leave rejected')
                                ->warning()
                                ->send();
                        } catch (HrLeaveRequestException $e) {
                            Notification::make()
                                ->title('Rejection failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
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
        return 'heroicon-o-clipboard-document-list';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'HR';
    }

    public static function getNavigationSort(): ?int
    {
        return 8;
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
            'index' => Pages\ListHrLeaveRequests::route('/'),
            'create' => Pages\CreateHrLeaveRequest::route('/create'),
            'edit' => Pages\EditHrLeaveRequest::route('/{record}/edit'),
        ];
    }
}
