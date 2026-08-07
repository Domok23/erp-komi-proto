<?php

namespace App\Livewire;

use App\Models\HrLeaveType;
use App\Services\CompanyContext;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component;

class ManageLeaveTypesModal extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Leave Type List')
            ->description('Manage leave categories, default quota, document requirements, and sickness settings.')
            ->query(
                HrLeaveType::query()
                    ->where('company_id', CompanyContext::getCompanyId())
            )
            ->columns([
                TextColumn::make('code')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('default_quota_days')
                    ->label('Default Quota Days')
                    ->sortable(),
                IconColumn::make('requires_document')
                    ->boolean(),
                IconColumn::make('is_sick_type')
                    ->label('Sick Leave')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->model(HrLeaveType::class)
                    ->schema([
                        TextInput::make('code')
                            ->required()
                            ->unique(
                                table: 'hr_leave_types',
                                column: 'code',
                                modifyRuleUsing: fn (Unique $rule) => $rule->where('company_id', CompanyContext::getCompanyId())
                            )
                            ->maxLength(50),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('default_quota_days')
                            ->label('Default Quota Days')
                            ->numeric()
                            ->minValue(0),
                        Toggle::make('requires_document')
                            ->default(false),
                        Toggle::make('is_sick_type')
                            ->label('Sick Leave Type')
                            ->default(false),
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
                                    table: 'hr_leave_types',
                                    column: 'code',
                                    ignoreRecord: true,
                                    modifyRuleUsing: fn (Unique $rule) => $rule->where('company_id', CompanyContext::getCompanyId())
                                )
                                ->maxLength(50),
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('default_quota_days')
                                ->label('Default Quota Days')
                                ->numeric()
                                ->minValue(0),
                            Toggle::make('requires_document')
                                ->default(false),
                            Toggle::make('is_sick_type')
                                ->label('Sick Leave Type')
                                ->default(false),
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
        return view('livewire.manage-leave-types-modal');
    }
}
