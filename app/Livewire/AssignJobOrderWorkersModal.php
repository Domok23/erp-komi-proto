<?php

namespace App\Livewire;

use App\Models\HrEmployee;
use App\Models\JobOrder;
use App\Models\ProductionOrder;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class AssignJobOrderWorkersModal extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public ?int $jobOrderId = null;

    public ?int $productionOrderId = null;

    public array $assignedWorkers = [];

    public function mount(?int $jobOrderId = null, ?int $productionOrderId = null, array $assignedWorkers = []): void
    {
        $this->jobOrderId = $jobOrderId;
        $this->productionOrderId = $productionOrderId;
        $this->assignedWorkers = $assignedWorkers;

        $this->loadSelectedRecords();
    }

    protected function loadSelectedRecords(): void
    {
        $initialNames = ! empty($this->assignedWorkers) ? $this->assignedWorkers : null;

        if (empty($initialNames) && $this->jobOrderId) {
            $jo = JobOrder::find($this->jobOrderId);
            if ($jo) {
                $initialNames = $jo->assigned_to;
            }
        }

        if (is_string($initialNames)) {
            $decoded = json_decode($initialNames, true);
            $initialNames = is_array($decoded) ? $decoded : [$initialNames];
        }

        if (! empty($initialNames) && is_array($initialNames)) {
            $this->selectedTableRecords = HrEmployee::query()
                ->whereIn('name', $initialNames)
                ->orWhereIn('employee_number', $initialNames)
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->toArray();
        }
    }

    public function table(Table $table): Table
    {
        if (empty($this->selectedTableRecords)) {
            $this->loadSelectedRecords();
        }

        $query = HrEmployee::query()->where('status', 'active');

        $poId = $this->productionOrderId;
        if (! $poId && $this->jobOrderId) {
            $jo = JobOrder::find($this->jobOrderId);
            $poId = $jo?->production_order_id;
        }

        if ($poId) {
            $po = ProductionOrder::with('project.activePlacements')->find($poId);
            if ($po && $po->project) {
                $employeeIds = $po->project->activePlacements->pluck('employee_id')->filter()->toArray();
                if (! empty($employeeIds)) {
                    $query->whereIn('id', $employeeIds);
                }
            }
        }

        return $table
            ->heading('Available Employees')
            ->description('Select employees from the table and click "Assign Selected Workers" to save.')
            ->currentSelectionLivewireProperty('selectedTableRecords')
            ->query($query)
            ->columns([
                TextColumn::make('employee_number')
                    ->label('Employee Number')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Employee Name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('department.name')
                    ->label('Department')
                    ->placeholder('-')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('position.name')
                    ->label('Position')
                    ->placeholder('-')
                    ->sortable()
                    ->searchable(),
            ])
            ->bulkActions([
                BulkAction::make('assign_workers')
                    ->label('Assign Selected Workers')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->action(function (Collection $records) {
                        $names = $records->pluck('name')->toArray();

                        if ($this->jobOrderId) {
                            $jo = JobOrder::find($this->jobOrderId);
                            if ($jo) {
                                $jo->update(['assigned_to' => $names]);
                                Notification::make()
                                    ->title('Job Order Workers Updated')
                                    ->body(count($names).' worker(s) assigned to Job Order #'.$jo->job_order_number)
                                    ->success()
                                    ->send();
                            }
                        }

                        $this->dispatch('workers-assigned', workers: $names);
                        $this->dispatch('set-job-order-workers', workers: $names);
                        $this->dispatch('close-modal', id: 'select_workers');
                        $this->dispatch('close-modal', id: 'assign_operators');
                        $this->dispatch('close-modal');
                    }),
            ]);
    }

    public function render()
    {
        return view('livewire.assign-job-order-workers-modal');
    }
}
