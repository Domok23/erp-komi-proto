<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HrCandidateResource\Pages;
use App\Filament\Resources\HrCandidateResource\RelationManagers;
use App\Models\HrCandidate;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HrCandidateResource extends Resource
{
    protected static ?string $model = HrCandidate::class;

    protected static ?string $navigationLabel = 'Candidates';

    protected static ?string $modelLabel = 'Candidate';

    protected static ?string $pluralModelLabel = 'Candidates';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Candidate Details')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('nik')
                        ->label('NIK')
                        ->validationAttribute('NIK')
                        ->unique(ignoreRecord: true)
                        ->maxLength(32),
                    Forms\Components\TextInput::make('phone')
                        ->tel()
                        ->maxLength(30),
                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->maxLength(100),
                    Forms\Components\Textarea::make('address')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('source')
                        ->maxLength(255),
                    Forms\Components\Select::make('status')
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
            Tables\Columns\TextColumn::make('name')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('nik')
                ->label('NIK')
                ->sortable()
                ->searchable(),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'screening' => 'gray',
                    'checklist' => 'info',
                    'ready_to_hire' => 'warning',
                    'hired' => 'success',
                    'rejected' => 'danger',
                    'withdrawn' => 'gray',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('phone')
                ->searchable(),
            Tables\Columns\TextColumn::make('hired_at')
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
        return 'heroicon-o-user-plus';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'HR';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ChecklistItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHrCandidates::route('/'),
            'create' => Pages\CreateHrCandidate::route('/create'),
            'edit' => Pages\EditHrCandidate::route('/{record}/edit'),
        ];
    }
}
