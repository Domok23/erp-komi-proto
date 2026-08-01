<?php

namespace App\Filament\Resources\HrAttendanceResource\Pages;

use App\Models\HrAttendance;
use App\Models\HrEmployee;
use App\Services\CompanyContext;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListHrAttendances extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\HrAttendanceResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('bulk_generate')
                ->label('Generate Bulk Attendance')
                ->icon('heroicon-o-user-group')
                ->color('info')
                ->form([
                    Forms\Components\DatePicker::make('date')
                        ->label('Date')
                        ->default(now()->toDateString())
                        ->required(),
                    Forms\Components\Select::make('default_status')
                        ->label('Default Status')
                        ->options([
                            'present' => 'Present',
                            'sick' => 'Sick',
                            'permission' => 'Permission',
                            'leave' => 'Leave',
                            'alpha' => 'Alpha',
                            'half_day' => 'Half Day',
                        ])
                        ->default('present')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $companyId = CompanyContext::getCompanyId();
                    $employees = HrEmployee::where('company_id', $companyId)
                        ->where('status', 'active')
                        ->get();

                    $createdCount = 0;
                    foreach ($employees as $employee) {
                        $created = HrAttendance::firstOrCreate(
                            [
                                'employee_id' => $employee->id,
                                'date' => $data['date'],
                            ],
                            [
                                'company_id' => $companyId,
                                'status' => $data['default_status'],
                                'created_by' => Auth::id(),
                            ]
                        );
                        if ($created->wasRecentlyCreated) {
                            $createdCount++;
                        }
                    }

                    Notification::make()
                        ->title('Bulk Attendance Generated')
                        ->body("Created {$createdCount} attendance record(s) for {$data['date']}.")
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
}
