<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HrWarningLetterResource\Pages;
use App\Models\HrEmployee;
use App\Models\HrWarningLetter;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HrWarningLetterResource extends Resource
{
    protected static ?string $model = HrWarningLetter::class;

    protected static ?string $navigationLabel = 'Warning Letters';

    protected static ?string $modelLabel = 'Warning Letter';

    protected static ?string $pluralModelLabel = 'Warning Letters';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Warning Letter Details')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Select::make('employee_id')
                        ->label('Employee')
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search): array {
                            return HrEmployee::query()
                                ->where(function (Builder $query) use ($search): void {
                                    $query->where('name', 'like', "%{$search}%")
                                        ->orWhere('employee_number', 'like', "%{$search}%");
                                })
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn (HrEmployee $employee): array => [
                                    $employee->id => "{$employee->name} ({$employee->employee_number})",
                                ])
                                ->all();
                        })
                        ->getOptionLabelUsing(function ($value): ?string {
                            $employee = HrEmployee::find($value);

                            return $employee ? "{$employee->name} ({$employee->employee_number})" : null;
                        })
                        ->required(),
                    Forms\Components\TextInput::make('letter_number')
                        ->required()
                        ->maxLength(50),
                    Forms\Components\DatePicker::make('issued_date')
                        ->required(),
                    Forms\Components\Textarea::make('reason')
                        ->required()
                        ->maxLength(65535)
                        ->columnSpanFull(),
                    Forms\Components\FileUpload::make('file_path')
                        ->directory('hr/warning-letters'),
                    Forms\Components\Textarea::make('notes')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('employee.name')
                ->label('Employee')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('letter_number')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('issued_date')
                ->date()
                ->sortable(),
            Tables\Columns\TextColumn::make('reason')
                ->limit(50),
        ])
            ->defaultSort('issued_date', 'desc')
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-exclamation-triangle';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'HR';
    }

    public static function getNavigationSort(): ?int
    {
        return 9;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHrWarningLetters::route('/'),
            'create' => Pages\CreateHrWarningLetter::route('/create'),
            'edit' => Pages\EditHrWarningLetter::route('/{record}/edit'),
        ];
    }
}
