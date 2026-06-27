<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use App\Services\CodeGenerator;
use App\Services\ProjectTransitionService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationLabel = 'Projects';

    protected static ?string $modelLabel = 'Project';

    protected static ?string $pluralModelLabel = 'Projects';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('project_code')
                ->disabled()
                ->dehydrated()
                ->default(fn () => CodeGenerator::generateProjectCode())
                ->required()
                ->maxLength(50),
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('type')
                ->options([
                    'proto' => 'Prototype',
                    'sample' => 'Sample',
                    'mass' => 'Mass Production',
                ])
                ->required(),
            Forms\Components\Select::make('status')
                ->options([
                    'planning' => 'Planning',
                    'development' => 'Development',
                    'sampling' => 'Sampling',
                    'approved' => 'Approved',
                    'production' => 'Production',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ])
                ->default('planning')
                ->required(),
            Forms\Components\Select::make('customer_id')
                ->relationship('customer', 'name')
                ->nullable(),
            Forms\Components\Select::make('sales_order_id')
                ->relationship('salesOrder', 'so_number')
                ->nullable(),
            Forms\Components\Select::make('design_id')
                ->relationship('design', 'name')
                ->nullable(),
            Forms\Components\Select::make('bom_id')
                ->relationship('bom', 'name')
                ->nullable(),
            Forms\Components\Select::make('reference_project_id')
                ->relationship('referenceProject', 'project_code')
                ->disabled()
                ->nullable(),
            Forms\Components\DatePicker::make('start_date'),
            Forms\Components\DatePicker::make('target_date'),
            Forms\Components\TextInput::make('target_qty')
                ->integer()
                ->default(0),
            Forms\Components\TextInput::make('produced_qty')
                ->integer()
                ->default(0),
            Section::make('Approval Details')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\DateTimePicker::make('approved_at')
                        ->disabled(),
                    Forms\Components\Select::make('approved_by')
                        ->relationship('approvedByUser', 'name')
                        ->disabled(),
                ])->columns(2),
            Forms\Components\Textarea::make('description')
                ->maxLength(65535)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('project_code')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
            Tables\Columns\BadgeColumn::make('type')
                ->color(fn (string $state): string => match ($state) {
                    'proto' => 'gray',
                    'sample' => 'info',
                    'mass' => 'success',
                    default => 'gray',
                }),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'planning' => 'gray',
                    'development' => 'info',
                    'sampling' => 'warning',
                    'approved' => 'primary',
                    'production' => 'warning',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('customer.name')->searchable(),
            Tables\Columns\TextColumn::make('target_qty')->numeric(),
            Tables\Columns\TextColumn::make('produced_qty')->numeric(),
        ])
            ->filters([
                SelectFilter::make('status')->options(['planning' => 'Planning', 'development' => 'Development', 'sampling' => 'Sampling', 'approved' => 'Approved', 'production' => 'Production', 'completed' => 'Completed', 'cancelled' => 'Cancelled']),
                SelectFilter::make('type')->options(['proto' => 'Prototype', 'sample' => 'Sample', 'mass' => 'Mass Production']),
                SelectFilter::make('customer_id')->relationship('customer', 'name'),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status !== 'approved')
                    ->action(function ($record) {
                        ProjectTransitionService::approveProject($record, Auth::id() ?? 1);
                        Notification::make()
                            ->title('Project Approved')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('info')
                    ->action(function ($record) {
                        $copy = ProjectTransitionService::duplicateProject($record);
                        Notification::make()
                            ->title('Project Duplicated: '.$copy->project_code)
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-folder';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Projects';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
