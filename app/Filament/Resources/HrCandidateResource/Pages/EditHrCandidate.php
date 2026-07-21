<?php

namespace App\Filament\Resources\HrCandidateResource\Pages;

use App\Exceptions\HrHireException;
use App\Filament\Resources\HrEmployeeResource;
use App\Models\HrCandidate;
use App\Models\HrDepartment;
use App\Models\HrPosition;
use App\Services\HireCandidateService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditHrCandidate extends EditRecord
{
    protected static string $resource = 'App\Filament\Resources\HrCandidateResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('hire')
                ->label('Hire')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (HrCandidate $record): bool => $record->status !== 'hired')
                ->form([
                    Forms\Components\TextInput::make('employee_number')
                        ->label('Employee Number (NIP)')
                        ->required()
                        ->maxLength(50),
                    Forms\Components\Select::make('department_id')
                        ->label('Department')
                        ->options(fn (): array => HrDepartment::query()->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('position_id')
                        ->label('Position')
                        ->options(fn (): array => HrPosition::query()->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload(),
                    Forms\Components\DatePicker::make('join_date')
                        ->required()
                        ->default(now()->toDateString()),
                    Forms\Components\Toggle::make('override')
                        ->label('Override checklist')
                        ->live(),
                    Forms\Components\Textarea::make('override_reason')
                        ->required(fn (Get $get): bool => (bool) $get('override'))
                        ->visible(fn (Get $get): bool => (bool) $get('override')),
                    Forms\Components\Select::make('contract_type')
                        ->label('Contract Type')
                        ->options([
                            'pkwt' => 'PKWT',
                            'pkwtt' => 'PKWTT',
                        ]),
                    Forms\Components\DatePicker::make('contract_start_date')
                        ->label('Contract Start Date'),
                    Forms\Components\DatePicker::make('contract_end_date')
                        ->label('Contract End Date'),
                ])
                ->action(function (array $data) {
                    try {
                        $employee = HireCandidateService::hire($this->record, [
                            'employee_number' => $data['employee_number'],
                            'department_id' => $data['department_id'] ?? null,
                            'position_id' => $data['position_id'] ?? null,
                            'join_date' => $data['join_date'],
                            'override' => (bool) ($data['override'] ?? false),
                            'override_reason' => $data['override_reason'] ?? null,
                            'contract' => ! empty($data['contract_type']) ? [
                                'type' => $data['contract_type'],
                                'start_date' => $data['contract_start_date'],
                                'end_date' => $data['contract_end_date'] ?? null,
                            ] : null,
                        ], Auth::id());

                        Notification::make()
                            ->title('Hired')
                            ->body("Employee {$employee->employee_number} created")
                            ->success()
                            ->send();

                        return redirect(HrEmployeeResource::getUrl('edit', ['record' => $employee]));
                    } catch (HrHireException $e) {
                        Notification::make()
                            ->title('Hire failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
