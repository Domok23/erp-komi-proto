<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Models\SubProject;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

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
                ->placeholder('Select an option')
                ->options([
                    'colorway' => 'Colorway',
                    'variant' => 'Shape / Size Variant',
                    'model' => 'Model / Style Sub-Type',
                    'component' => 'Component / Sub-Assembly',
                    'other' => 'Other',
                ])
                ->default(null)
                ->nullable(),
            Forms\Components\Select::make('design_id')
                ->label('R&D Override (Optional)')
                ->relationship('design', 'name')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->formatted_select_label)
                ->searchable(['name', 'code'])
                ->preload()
                ->nullable(),
            Forms\Components\TextInput::make('target_qty')
                ->integer()
                ->default(0)
                ->minValue(0),
            Forms\Components\TextInput::make('produced_qty')
                ->label(new HtmlString('Produced Qty <span title="Total actual completed quantity produced (calculated automatically from Production Orders)" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                ->integer()
                ->default(0)
                ->disabled()
                ->dehydrated(false),
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
                Tables\Columns\TextColumn::make('design.name')
                    ->label('R&D Override')
                    ->formatStateUsing(fn ($record) => $record->design?->formatted_select_label ?? 'Inherited')
                    ->badge()
                    ->color(fn ($record) => $record->design_id ? 'primary' : 'gray'),
                Tables\Columns\TextColumn::make('target_qty')->numeric(),
                Tables\Columns\TextColumn::make('produced_qty')->numeric(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->headerTooltip('Inherited from Master Project')
                    ->tooltip('Inherited from Master Project')
                    ->extraAttributes([
                        'title' => 'Inherited from Master Project',
                    ])
                    ->extraCellAttributes([
                        'title' => 'Inherited from Master Project',
                    ])
                    ->color(fn (?string $state): string => match ($state) {
                        'planning' => 'warning',
                        'approved' => 'success',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->hidden(fn () => $this->getOwnerRecord()?->type === 'mass'),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make()
                        ->hidden(fn ($record) => $record->project?->type === 'mass')
                        ->before(function (SubProject $record, DeleteAction $action) {
                            $blockers = $record->getDeletionBlockers();
                            if (! empty($blockers)) {
                                Notification::make()
                                    ->title('Cannot Delete Sub-Project')
                                    ->body("Sub-Project '{$record->name}' cannot be deleted because it is linked to: ".implode(', ', $blockers).'. Please remove the related records first.')
                                    ->danger()
                                    ->persistent()
                                    ->send();

                                $action->halt();
                            }
                        }),
                ]),
            ]);
    }
}
