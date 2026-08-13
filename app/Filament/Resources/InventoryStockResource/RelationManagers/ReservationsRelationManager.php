<?php

namespace App\Filament\Resources\InventoryStockResource\RelationManagers;

use App\Filament\Resources\MaterialReservations\MaterialReservationResource;
use Filament\Actions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ReservationsRelationManager extends RelationManager
{
    protected static string $relationship = 'reservations';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('document_number')
            ->columns([
                Tables\Columns\TextColumn::make('document_number')->sortable()->searchable(),
                Tables\Columns\BadgeColumn::make('reservation_type')
                    ->color(fn (string $state): string => $state === 'project' ? 'primary' : 'gray'),
                Tables\Columns\TextColumn::make('project.project_code')->label('Project')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('reserved_qty')->numeric()->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending' => 'warning',
                        'approved' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('reservation_date')->date()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('reservation_type')->options([
                    'project' => 'Project Specific',
                    'general' => 'General/Buffer',
                ]),
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'pending' => 'Pending Approval',
                    'approved' => 'Approved',
                    'cancelled' => 'Cancelled',
                ]),
            ])
            ->headerActions([
                Actions\CreateAction::make()->url(MaterialReservationResource::getUrl('create')),
            ])
            ->recordActions([
                Actions\ViewAction::make()->url(fn ($record) => MaterialReservationResource::getUrl('edit', ['record' => $record->id])),
            ]);
    }
}
