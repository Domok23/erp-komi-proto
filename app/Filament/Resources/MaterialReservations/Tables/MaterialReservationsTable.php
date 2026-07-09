<?php

namespace App\Filament\Resources\MaterialReservations\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables;

class MaterialReservationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('document_number')->sortable()->searchable(),
                Tables\Columns\BadgeColumn::make('reservation_type')
                    ->color(fn (string $state): string => $state === 'project' ? 'primary' : 'gray'),
                Tables\Columns\TextColumn::make('project.project_code')->label('Project')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('warehouse.name')->sortable(),
                Tables\Columns\TextColumn::make('material.name')->sortable()->searchable(),
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
                SelectFilter::make('reservation_type')->options([
                    'project' => 'Project Specific',
                    'general' => 'General/Buffer',
                ]),
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'prompting' => 'Pending Approval',
                    'approved' => 'Approved',
                    'cancelled' => 'Cancelled',
                ]),
                SelectFilter::make('warehouse_id')->relationship('warehouse', 'name'),
                SelectFilter::make('project_id')->relationship('project', 'project_code'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
