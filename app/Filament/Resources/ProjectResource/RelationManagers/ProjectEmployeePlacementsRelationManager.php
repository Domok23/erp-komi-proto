<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Exceptions\HrPlacementException;
use App\Filament\Resources\HrEmployeeResource;
use App\Models\HrEmployee;
use App\Models\HrEmployeePlacement;
use App\Services\PlaceEmployeeService;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class ProjectEmployeePlacementsRelationManager extends RelationManager
{
    protected static string $relationship = 'placements';

    protected static ?string $title = 'Team Members / Employee Placements';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('employee_id')
                ->label('Employee')
                ->options(function (RelationManager $livewire) {
                    $companyId = $livewire->getOwnerRecord()->company_id;

                    return HrEmployee::query()
                        ->where('company_id', $companyId)
                        ->where('status', 'active')
                        ->get()
                        ->mapWithKeys(fn (HrEmployee $emp) => [
                            $emp->id => "[{$emp->employee_number}] {$emp->name}",
                        ]);
                })
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\DatePicker::make('start_date')
                ->required()
                ->default(now()->toDateString()),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.employee_number')
                    ->label('Employee No')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Employee Name')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($state, HrEmployeePlacement $record) => new HtmlString(
                        '<a href="'.HrEmployeeResource::getUrl('edit', ['record' => $record->employee_id]).'" class="ref-link">'.$state.'</a>'
                    )),
                Tables\Columns\TextColumn::make('employee.department.name')
                    ->label('Department')
                    ->sortable()
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('employee.position.name')
                    ->label('Position')
                    ->sortable()
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->placeholder('Present'),
                Tables\Columns\BadgeColumn::make('status')
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'ended' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('start_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'ended' => 'Ended',
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Assign Employee')
                    ->using(function (array $data, RelationManager $livewire): Model {
                        try {
                            return PlaceEmployeeService::place(
                                HrEmployee::findOrFail($data['employee_id']),
                                $livewire->getOwnerRecord(),
                                $data['start_date'],
                                Auth::id() ?? 1
                            );
                        } catch (HrPlacementException $e) {
                            Notification::make()
                                ->title('Placement failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            throw new Halt();
                        }
                    }),
            ])
            ->actions([
                ViewAction::make(),
            ]);
    }
}
