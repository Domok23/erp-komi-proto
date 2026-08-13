<?php

namespace App\Livewire;

use App\Exceptions\HrHireException;
use App\Models\HrCandidate;
use App\Models\HrDepartment;
use App\Models\HrPosition;
use App\Services\CompanyContext;
use App\Services\HireCandidateService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component;

class ManageCandidatesModal extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Candidate List')
            ->description('Manage job candidates, screening status, and hiring process.')
            ->query(
                HrCandidate::query()
                    ->where('company_id', CompanyContext::getCompanyId())
            )
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('nik')
                    ->label('NIK')
                    ->sortable()
                    ->searchable(),
                BadgeColumn::make('status')
                    ->color(fn (string $state): string => match ($state) {
                        'screening' => 'gray',
                        'checklist' => 'info',
                        'ready_to_hire' => 'warning',
                        'hired' => 'success',
                        'rejected' => 'danger',
                        'withdrawn' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('hired_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'screening' => 'Screening',
                        'checklist' => 'Checklist',
                        'ready_to_hire' => 'Ready to Hire',
                        'hired' => 'Hired',
                        'rejected' => 'Rejected',
                        'withdrawn' => 'Withdrawn',
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->model(HrCandidate::class)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('nik')
                            ->label('NIK')
                            ->unique(
                                table: 'hr_candidates',
                                column: 'nik',
                                modifyRuleUsing: fn (Unique $rule) => $rule->where('company_id', CompanyContext::getCompanyId())
                            )
                            ->maxLength(32),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(30),
                        TextInput::make('email')
                            ->email()
                            ->maxLength(100),
                        Textarea::make('address')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        TextInput::make('source')
                            ->maxLength(255),
                        Select::make('status')
                            ->options([
                                'screening' => 'Screening',
                                'checklist' => 'Checklist',
                                'ready_to_hire' => 'Ready to Hire',
                                'rejected' => 'Rejected',
                                'withdrawn' => 'Withdrawn',
                            ])
                            ->default('screening')
                            ->required(),
                        Textarea::make('notes')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['company_id'] = CompanyContext::getCompanyId();

                        return $data;
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('nik')
                                ->label('NIK')
                                ->unique(
                                    table: 'hr_candidates',
                                    column: 'nik',
                                    ignoreRecord: true,
                                    modifyRuleUsing: fn (Unique $rule) => $rule->where('company_id', CompanyContext::getCompanyId())
                                )
                                ->maxLength(32),
                            TextInput::make('phone')
                                ->tel()
                                ->maxLength(30),
                            TextInput::make('email')
                                ->email()
                                ->maxLength(100),
                            Textarea::make('address')
                                ->maxLength(65535)
                                ->columnSpanFull(),
                            TextInput::make('source')
                                ->maxLength(255),
                            Select::make('status')
                                ->options(fn ($record): array => $record?->status === 'hired' ? [
                                    'hired' => 'Hired',
                                ] : [
                                    'screening' => 'Screening',
                                    'checklist' => 'Checklist',
                                    'ready_to_hire' => 'Ready to Hire',
                                    'rejected' => 'Rejected',
                                    'withdrawn' => 'Withdrawn',
                                ])
                                ->disabled(fn ($record): bool => $record?->status === 'hired')
                                ->default('screening')
                                ->required(),
                            Textarea::make('notes')
                                ->maxLength(65535)
                                ->columnSpanFull(),
                        ]),
                    Action::make('checklist')
                        ->label('Checklist')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->color('info')
                        ->modalHeading(fn (HrCandidate $record): string => "Hiring Checklist - {$record->name}")
                        ->modalWidth('4xl')
                        ->fillForm(fn (HrCandidate $record): array => [
                            'checklistItems' => $record->checklistItems()
                                ->get()
                                ->map(fn ($item) => [
                                    'id' => $item->id,
                                    'type' => $item->type,
                                    'label' => $item->label,
                                    'is_required' => (bool) $item->is_required,
                                    'status' => $item->status,
                                    'file_path' => $item->file_path,
                                    'notes' => $item->notes,
                                ])
                                ->toArray(),
                        ])
                        ->schema([
                            Repeater::make('checklistItems')
                                ->label('Checklist Items')
                                ->schema([
                                    Select::make('type')
                                        ->options([
                                            'mcu' => 'MCU',
                                            'bank_account' => 'Bank Account',
                                            'other' => 'Other',
                                        ])
                                        ->required(),
                                    TextInput::make('label')
                                        ->required(),
                                    Toggle::make('is_required')
                                        ->label('Required')
                                        ->default(true),
                                    Select::make('status')
                                        ->options([
                                            'pending' => 'Pending',
                                            'done' => 'Done',
                                            'waived' => 'Waived',
                                        ])
                                        ->default('pending')
                                        ->required(),
                                    FileUpload::make('file_path')
                                        ->directory('hr/checklist'),
                                    Textarea::make('notes')
                                        ->maxLength(65535)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->defaultItems(0)
                                ->addActionLabel('Add Checklist Item')
                                ->reorderable(false),
                        ])
                        ->action(function (HrCandidate $record, array $data): void {
                            $items = $data['checklistItems'] ?? [];
                            $existingIds = [];

                            foreach ($items as $itemData) {
                                if (! empty($itemData['id'])) {
                                    $existingIds[] = $itemData['id'];
                                    $record->checklistItems()->where('id', $itemData['id'])->update([
                                        'type' => $itemData['type'],
                                        'label' => $itemData['label'],
                                        'is_required' => (bool) ($itemData['is_required'] ?? true),
                                        'status' => $itemData['status'],
                                        'file_path' => $itemData['file_path'] ?? null,
                                        'notes' => $itemData['notes'] ?? null,
                                    ]);
                                } else {
                                    $newItem = $record->checklistItems()->create([
                                        'type' => $itemData['type'],
                                        'label' => $itemData['label'],
                                        'is_required' => (bool) ($itemData['is_required'] ?? true),
                                        'status' => $itemData['status'] ?? 'pending',
                                        'file_path' => $itemData['file_path'] ?? null,
                                        'notes' => $itemData['notes'] ?? null,
                                    ]);
                                    $existingIds[] = $newItem->id;
                                }
                            }

                            $record->checklistItems()->whereNotIn('id', $existingIds)->delete();

                            Notification::make()
                                ->title('Checklist Saved')
                                ->body("Hiring checklist for {$record->name} updated successfully")
                                ->success()
                                ->send();
                        }),
                    Action::make('hire')
                        ->label('Hire')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->visible(fn (HrCandidate $record): bool => $record->status !== 'hired')
                        ->schema([
                            TextInput::make('employee_number')
                                ->label('Employee Number (NIP)')
                                ->required()
                                ->unique(
                                    table: 'hr_employees',
                                    column: 'employee_number',
                                    ignoreRecord: false,
                                    modifyRuleUsing: fn (Unique $rule) => $rule->where('company_id', CompanyContext::getCompanyId())
                                )
                                ->maxLength(50),
                            Select::make('department_id')
                                ->label('Department')
                                ->options(fn (): array => HrDepartment::query()->where('company_id', CompanyContext::getCompanyId())->pluck('name', 'id')->all())
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(fn (callable $set) => $set('position_id', null)),
                            Select::make('position_id')
                                ->label('Position')
                                ->options(function (callable $get): array {
                                    $departmentId = $get('department_id');
                                    if (! $departmentId) {
                                        return [];
                                    }

                                    return HrPosition::query()
                                        ->where('company_id', CompanyContext::getCompanyId())
                                        ->where('department_id', $departmentId)
                                        ->pluck('name', 'id')
                                        ->all();
                                })
                                ->searchable()
                                ->preload()
                                ->disabled(fn (callable $get): bool => blank($get('department_id'))),
                            DatePicker::make('join_date')
                                ->required()
                                ->default(now()->toDateString()),
                            Toggle::make('override')
                                ->label('Override checklist')
                                ->live(),
                            Textarea::make('override_reason')
                                ->required(fn (callable $get): bool => (bool) $get('override'))
                                ->visible(fn (callable $get): bool => (bool) $get('override')),
                            Select::make('contract_type')
                                ->label('Contract Type')
                                ->options([
                                    'pkwt' => 'PKWT',
                                    'pkwtt' => 'PKWTT',
                                ]),
                            DatePicker::make('contract_start_date')
                                ->label('Contract Start Date'),
                            DatePicker::make('contract_end_date')
                                ->label('Contract End Date')
                                ->afterOrEqual('contract_start_date'),
                        ])
                        ->action(function (HrCandidate $record, array $data) {
                            try {
                                $employee = HireCandidateService::hire($record, [
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
                                    ->body("Employee {$employee->employee_number} created successfully")
                                    ->success()
                                    ->send();
                            } catch (HrHireException $e) {
                                Notification::make()
                                    ->title('Hire failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function render()
    {
        return view('livewire.manage-candidates-modal');
    }
}
