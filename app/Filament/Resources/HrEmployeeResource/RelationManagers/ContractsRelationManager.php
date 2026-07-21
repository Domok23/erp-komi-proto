<?php

namespace App\Filament\Resources\HrEmployeeResource\RelationManagers;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ContractsRelationManager extends RelationManager
{
    protected static string $relationship = 'contracts';

    protected static ?string $title = 'Employment Contracts';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('type')
                ->options([
                    'pkwt' => 'PKWT',
                    'pkwtt' => 'PKWTT',
                ])
                ->required(),
            Forms\Components\DatePicker::make('start_date')
                ->required(),
            Forms\Components\DatePicker::make('end_date'),
            Forms\Components\FileUpload::make('file_path')
                ->directory('hr/contracts'),
            Forms\Components\Select::make('status')
                ->options([
                    'active' => 'Active',
                    'ended' => 'Ended',
                ])
                ->default('active')
                ->required(),
            Forms\Components\Textarea::make('notes')
                ->maxLength(65535)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'ended' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('notes')
                    ->limit(50),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data, RelationManager $livewire): Model {
                        $employee = $livewire->getOwnerRecord();

                        if (($data['status'] ?? 'active') === 'active') {
                            $employee->contracts()->where('status', 'active')->update(['status' => 'ended']);
                        }

                        return $employee->contracts()->create($data);
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->using(function (array $data, Model $record): Model {
                            if (($data['status'] ?? $record->status) === 'active') {
                                $record->employee->contracts()
                                    ->where('status', 'active')
                                    ->where('id', '!=', $record->id)
                                    ->update(['status' => 'ended']);
                            }

                            $record->update($data);

                            return $record;
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
}
