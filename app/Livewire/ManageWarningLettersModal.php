<?php

namespace App\Livewire;

use App\Models\HrEmployee;
use App\Models\HrWarningLetter;
use App\Services\CompanyContext;
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
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component;

class ManageWarningLettersModal extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Warning Letter List')
            ->description('Manage employee warning letters and disciplinary records.')
            ->query(
                HrWarningLetter::query()
                    ->where('company_id', CompanyContext::getCompanyId())
            )
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Employee')
                    ->sortable()
                    ->searchable(),
                BadgeColumn::make('level')
                    ->label('Level')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'sp_1' => 'SP 1',
                        'sp_2' => 'SP 2',
                        'sp_3' => 'SP 3',
                        default => strtoupper($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'sp_1' => 'warning',
                        'sp_2' => 'danger',
                        'sp_3' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('letter_number')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('issued_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('reason')
                    ->limit(50),
            ])
            ->defaultSort('issued_date', 'desc')
            ->filters([
                SelectFilter::make('level')
                    ->options([
                        'sp_1' => 'SP 1',
                        'sp_2' => 'SP 2',
                        'sp_3' => 'SP 3',
                    ]),
                SelectFilter::make('employee_id')
                    ->label('Employee')
                    ->options(
                        fn () => HrEmployee::query()
                            ->where('company_id', CompanyContext::getCompanyId())
                            ->pluck('name', 'id')
                            ->all()
                    ),
            ])
            ->headerActions([
                CreateAction::make()
                    ->model(HrWarningLetter::class)
                    ->schema([
                        Select::make('employee_id')
                            ->label('Employee')
                            ->options(function (): array {
                                return HrEmployee::query()
                                    ->where('company_id', CompanyContext::getCompanyId())
                                    ->where('status', 'active')
                                    ->get()
                                    ->mapWithKeys(fn (HrEmployee $employee): array => [
                                        $employee->id => "{$employee->name} ({$employee->employee_number})",
                                    ])
                                    ->all();
                            })
                            ->searchable()
                            ->required(),
                        Select::make('level')
                            ->label('SP Level')
                            ->options([
                                'sp_1' => 'SP 1',
                                'sp_2' => 'SP 2',
                                'sp_3' => 'SP 3',
                            ])
                            ->default('sp_1')
                            ->required(),
                        TextInput::make('letter_number')
                            ->required()
                            ->unique(
                                table: 'hr_warning_letters',
                                column: 'letter_number',
                                modifyRuleUsing: fn (Unique $rule) => $rule->where('company_id', CompanyContext::getCompanyId())
                            )
                            ->maxLength(50),
                        DatePicker::make('issued_date')
                            ->required()
                            ->maxDate(now()),
                        Textarea::make('reason')
                            ->required()
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        FileUpload::make('file_path')
                            ->directory('hr/warning-letters'),
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
                            Select::make('employee_id')
                                ->label('Employee')
                                ->options(function (): array {
                                    return HrEmployee::query()
                                        ->where('company_id', CompanyContext::getCompanyId())
                                        ->get()
                                        ->mapWithKeys(fn (HrEmployee $employee): array => [
                                            $employee->id => "{$employee->name} ({$employee->employee_number})",
                                        ])
                                        ->all();
                                })
                                ->searchable()
                                ->required(),
                            Select::make('level')
                                ->label('SP Level')
                                ->options([
                                    'sp_1' => 'SP 1',
                                    'sp_2' => 'SP 2',
                                    'sp_3' => 'SP 3',
                                ])
                                ->required(),
                            TextInput::make('letter_number')
                                ->required()
                                ->unique(
                                    table: 'hr_warning_letters',
                                    column: 'letter_number',
                                    ignoreRecord: true,
                                    modifyRuleUsing: fn (Unique $rule) => $rule->where('company_id', CompanyContext::getCompanyId())
                                )
                                ->maxLength(50),
                            DatePicker::make('issued_date')
                                ->required()
                                ->maxDate(now()),
                            Textarea::make('reason')
                                ->required()
                                ->maxLength(65535)
                                ->columnSpanFull(),
                            FileUpload::make('file_path')
                                ->directory('hr/warning-letters'),
                            Textarea::make('notes')
                                ->maxLength(65535)
                                ->columnSpanFull(),
                        ]),
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
        return view('livewire.manage-warning-letters-modal');
    }
}
