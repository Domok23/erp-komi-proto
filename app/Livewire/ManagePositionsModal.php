<?php

namespace App\Livewire;

use App\Models\HrDepartment;
use App\Models\HrPosition;
use App\Services\CompanyContext;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component;

class ManagePositionsModal extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Position List')
            ->description('Manage job positions and department assignments.')
            ->query(
                HrPosition::query()
                    ->where('company_id', CompanyContext::getCompanyId())
            )
            ->columns([
                TextColumn::make('code')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('department.name')
                    ->label('Department')
                    ->placeholder('Orphan / Unassigned')
                    ->sortable()
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name', fn ($query) => $query->where('company_id', CompanyContext::getCompanyId())),
                SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->model(HrPosition::class)
                    ->schema([
                        TextInput::make('code')
                            ->required()
                            ->unique(
                                table: 'hr_positions',
                                column: 'code',
                                modifyRuleUsing: fn (Unique $rule) => $rule->where('company_id', CompanyContext::getCompanyId())
                            )
                            ->maxLength(50),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Select::make('department_id')
                            ->label('Department')
                            ->options(
                                fn () => HrDepartment::query()
                                    ->where('company_id', CompanyContext::getCompanyId())
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->nullable(),
                        Toggle::make('is_active')
                            ->default(true),
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
                            TextInput::make('code')
                                ->required()
                                ->unique(
                                    table: 'hr_positions',
                                    column: 'code',
                                    ignoreRecord: true,
                                    modifyRuleUsing: fn (Unique $rule) => $rule->where('company_id', CompanyContext::getCompanyId())
                                )
                                ->maxLength(50),
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Select::make('department_id')
                                ->label('Department')
                                ->options(
                                    fn () => HrDepartment::query()
                                        ->where('company_id', CompanyContext::getCompanyId())
                                        ->pluck('name', 'id')
                                )
                                ->searchable()
                                ->nullable(),
                            Toggle::make('is_active')
                                ->default(true),
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
        return view('livewire.manage-positions-modal');
    }
}
