<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HrWarningLetterResource\Pages;
use App\Models\HrEmployee;
use App\Models\HrWarningLetter;
use App\Services\CompanyContext;
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
use Illuminate\Validation\Rules\Unique;

class HrWarningLetterResource extends Resource
{
    protected static ?string $model = HrWarningLetter::class;

    protected static ?string $navigationLabel = 'Warning Letters';

    protected static ?string $modelLabel = 'Warning Letter';

    protected static ?string $pluralModelLabel = 'Warning Letters';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Warning Letter Details')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Select::make('employee_id')
                        ->label('Employee')
                        ->options(function (): array {
                            return HrEmployee::query()
                                ->where('company_id', CompanyContext::getCompanyId())
                                ->where('status', 'active')
                                ->get()
                                ->mapWithKeys(fn (HrEmployee $employee): array => [
                                    $employee->id => "{$employee->name} <span style=\"color: #6b7280; font-size: 0.875em;\">({$employee->employee_number})</span>",
                                ])
                                ->all();
                        })
                        ->allowHtml()
                        ->searchable()
                        ->required(),
                    Forms\Components\Select::make('level')
                        ->label('SP Level')
                        ->options([
                            'sp_1' => 'SP 1',
                            'sp_2' => 'SP 2',
                            'sp_3' => 'SP 3',
                        ])
                        ->default('sp_1')
                        ->required(),
                    Forms\Components\TextInput::make('letter_number')
                        ->required()
                        ->unique(
                            table: 'hr_warning_letters',
                            column: 'letter_number',
                            ignoreRecord: true,
                            modifyRuleUsing: fn (Unique $rule) => $rule->where('company_id', CompanyContext::getCompanyId())
                        )
                        ->maxLength(50),
                    Forms\Components\DatePicker::make('issued_date')
                        ->required()
                        ->maxDate(now()),
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
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('employee'))
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Employee')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('level')
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
