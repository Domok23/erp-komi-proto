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
use Illuminate\Validation\Rules\Unique;

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
                        ->unique(
                            table: 'hr_employees',
                            column: 'employee_number',
                            modifyRuleUsing: fn (Unique $rule) => $rule->where('company_id', $this->record->company_id)
                        )
                        ->maxLength(50),
                    Forms\Components\Select::make('department_id')
                        ->label('Department')
                        ->options(fn (): array => HrDepartment::query()->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn (callable $set) => $set('position_id', null)),
                    Forms\Components\Select::make('position_id')
                        ->label('Position')
                        ->options(function (callable $get): array {
                            $departmentId = $get('department_id');
                            if (! $departmentId) {
                                return [];
                            }

                            return HrPosition::query()
                                ->where('department_id', $departmentId)
                                ->pluck('name', 'id')
                                ->all();
                        })
                        ->searchable()
                        ->preload()
                        ->disabled(fn (callable $get): bool => blank($get('department_id'))),
                    Forms\Components\DatePicker::make('join_date')
                        ->required()
                        ->default(now()->toDateString()),
                    Forms\Components\Toggle::make('override')
                        ->label('Override checklist')
                        ->live(),
                    Forms\Components\Textarea::make('override_reason')
                        ->required(fn ($get): bool => (bool) $get('override'))
                        ->visible(fn ($get): bool => (bool) $get('override')),
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

                        return $this->redirect(HrEmployeeResource::getUrl('edit', ['record' => $employee]), navigate: true);
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
