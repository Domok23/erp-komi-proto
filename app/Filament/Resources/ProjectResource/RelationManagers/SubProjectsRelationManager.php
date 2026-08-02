<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Services\SubProjectService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SubProjectsRelationManager extends RelationManager
{
    protected static string $relationship = 'subProjects';

    protected static ?string $title = 'Sub-Projects';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('code')
                ->maxLength(50)
                ->nullable(),
            Forms\Components\Select::make('category')
                ->options([
                    'colorway' => 'Colorway / Varian Warna',
                    'variant' => 'Varian Bentuk / Ukuran',
                    'model' => 'Model / Style Sub-Type',
                    'component' => 'Komponen / Sub-Assembly',
                    'other' => 'Lainnya',
                ])
                ->default('variant')
                ->nullable(),
            Forms\Components\Select::make('bom_id')
                ->label('BOM Override (Optional)')
                ->relationship('bom', 'name')
                ->searchable()
                ->preload()
                ->nullable(),
            Forms\Components\TextInput::make('target_qty')
                ->integer()
                ->default(0)
                ->minValue(0),
            Forms\Components\TextInput::make('produced_qty')
                ->integer()
                ->default(0)
                ->minValue(0),
            Forms\Components\Select::make('review_status')
                ->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ])
                ->default('pending')
                ->required(),
            Forms\Components\Textarea::make('review_notes')
                ->maxLength(65535)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('code')->sortable()->searchable(),
                Tables\Columns\BadgeColumn::make('category')
                    ->colors([
                        'info' => 'colorway',
                        'primary' => 'variant',
                        'warning' => 'model',
                        'success' => 'component',
                        'gray' => 'other',
                    ]),
                Tables\Columns\TextColumn::make('target_qty')->numeric(),
                Tables\Columns\TextColumn::make('produced_qty')->numeric(),
                Tables\Columns\BadgeColumn::make('review_status')
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('reviewed_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('reviewedByUser.name')->label('Reviewed By'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                Action::make('approve_review')
                    ->label('Approve Review')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->review_status !== 'approved')
                    ->action(function ($record) {
                        SubProjectService::setReviewStatus($record, 'approved', Auth::id() ?? 1);
                    }),
                Action::make('reject_review')
                    ->label('Reject Review')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn ($record) => $record->review_status !== 'rejected')
                    ->action(function ($record) {
                        SubProjectService::setReviewStatus($record, 'rejected', Auth::id() ?? 1);
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
