<?php

namespace App\Filament\Resources\HrCandidateResource\RelationManagers;

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

class ChecklistItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'checklistItems';

    protected static ?string $title = 'Hiring Checklist';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('type')
                ->options([
                    'mcu' => 'MCU',
                    'bank_account' => 'Bank Account',
                    'other' => 'Other',
                ])
                ->required(),
            Forms\Components\TextInput::make('label')
                ->required()
                ->maxLength(255),
            Forms\Components\Toggle::make('is_required')
                ->default(true),
            Forms\Components\Select::make('status')
                ->options([
                    'pending' => 'Pending',
                    'done' => 'Done',
                    'waived' => 'Waived',
                ])
                ->default('pending')
                ->required(),
            Forms\Components\FileUpload::make('file_path')
                ->directory('hr/checklist'),
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
                Tables\Columns\TextColumn::make('label')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_required')
                    ->boolean(),
                Tables\Columns\BadgeColumn::make('status')
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'done' => 'success',
                        'waived' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('notes')
                    ->limit(50),
            ])
            ->headerActions([
                CreateAction::make(),
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
}
