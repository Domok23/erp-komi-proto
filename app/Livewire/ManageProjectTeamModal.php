<?php

namespace App\Livewire;

use App\Models\HrEmployee;
use App\Models\HrEmployeePlacement;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

class ManageProjectTeamModal extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public ?int $projectId = null;

    public bool $isReadOnly = false;

    public function mount(?int $projectId = null, bool $isReadOnly = false): void
    {
        $this->projectId = $projectId;
        $this->isReadOnly = $isReadOnly;
    }

    public function table(Table $table): Table
    {
        $query = HrEmployeePlacement::query()
            ->with(['employee.department', 'employee.position'])
            ->where('project_id', $this->projectId);

        $table = $table
            ->heading('Project Team Members')
            ->description('List of employees collaborating on this project.')
            ->query($query)
            ->columns([
                TextColumn::make('employee.employee_number')
                    ->label('NIP')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('employee.name')
                    ->label('Employee Name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('employee.department.name')
                    ->label('Department')
                    ->placeholder('-')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('employee.position.name')
                    ->label('Position')
                    ->placeholder('-')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'ended' => 'gray',
                        default => 'info',
                    }),
            ]);

        if (! $this->isReadOnly) {
            $table->headerActions([
                Action::make('add_member')
                    ->label('Add Team Member')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->form([
                        Select::make('employee_id')
                            ->label('Employee')
                            ->options(
                                fn () => HrEmployee::where('status', 'active')
                                    ->get()
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->required(),
                        DatePicker::make('start_date')
                            ->default(now())
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        if ($this->projectId) {
                            HrEmployeePlacement::create([
                                'project_id' => $this->projectId,
                                'employee_id' => $data['employee_id'],
                                'start_date' => $data['start_date'],
                                'status' => 'active',
                            ]);
                            Notification::make()
                                ->title('Team Member Added')
                                ->success()
                                ->send();
                        }
                    }),
            ])->actions([
                Action::make('end_placement')
                    ->label('End Placement')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'active')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'ended',
                            'end_date' => now(),
                        ]);
                        Notification::make()
                            ->title('Placement Ended')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ]);
        }

        return $table;
    }

    public function render()
    {
        return view('livewire.manage-project-team-modal');
    }
}
