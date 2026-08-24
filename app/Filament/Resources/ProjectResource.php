<?php

namespace App\Filament\Resources;

use App\Exceptions\ProjectArchiveException;
use App\Exceptions\SubProjectException;
use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers\SubProjectsRelationManager;
use App\Livewire\ProjectDesignConsumptionTable;
use App\Models\Bom;
use App\Models\Project;
use App\Models\RdDesign;
use App\Services\CodeGenerator;
use App\Services\ProjectArchiveService;
use App\Services\ProjectTransitionService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationLabel = 'Projects';

    protected static ?string $modelLabel = 'Project';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'project_code'];
    }

    protected static ?string $pluralModelLabel = 'Projects';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Project Details')
                ->columnSpanFull()
                ->headerActions([
                    Action::make('manage_team')
                        ->label('Project Team Members')
                        ->icon('heroicon-o-user-group')
                        ->color('primary')
                        ->modalHeading('Project Team Members (Collaborators)')
                        ->modalContent(fn ($record) => view('filament.pages.manage-project-team-modal-wrapper', [
                            'projectId' => $record?->id,
                            'isReadOnly' => false,
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->modalWidth('4xl')
                        ->visible(fn ($record) => $record !== null),
                ])
                ->schema([
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
                        ->getOptionLabelFromRecordUsing(fn ($record) => new HtmlString('<a href="'.CustomerResource::getUrl('edit', ['record' => $record]).'" class="ref-link">'.$record->name.'</a>'))
                        ->allowHtml()
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Forms\Components\Select::make('sales_order_id')
                        ->relationship('salesOrder', 'so_number')
                        ->getOptionLabelFromRecordUsing(fn ($record) => new HtmlString('<a href="'.SalesOrderResource::getUrl('edit', ['record' => $record]).'" class="ref-link">'.$record->so_number.'</a>'))
                        ->allowHtml()
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Forms\Components\Select::make('design_id')
                        ->label('R&D Design')
                        ->relationship('design', 'name')
                        ->getOptionLabelFromRecordUsing(function ($record) {
                            $url = RdDesignResource::getUrl('edit', ['record' => $record]);
                            $labelText = "{$record->code} - {$record->name} (v{$record->version})";
                            if ($record->status === 'approved') {
                                return new HtmlString('<a href="'.$url.'" class="ref-link">'.$labelText.'</a>');
                            }

                            return new HtmlString('<a href="'.$url.'" class="ref-link">'.$labelText.'</a> <span style="color: #888; font-size: 0.9em; margin-left: 5px;">['.$record->status.']</span>');
                        })
                        ->allowHtml()
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $design = RdDesign::find($state);
                                if ($design && $design->status !== 'approved') {
                                    $set('design_id', null);

                                    $title = match ($design->status) {
                                        'draft' => 'Design is Still Draft',
                                        'under_review' => 'Design is Under Review',
                                        'archived', 'obsolete' => 'Design Inactive / Archived',
                                        default => 'Invalid R&D Design',
                                    };

                                    $body = match ($design->status) {
                                        'draft' => 'R&D Design "'.$design->name.'" is still draft and has not been approved yet.',
                                        'under_review' => 'R&D Design "'.$design->name.'" is currently under review.',
                                        'archived', 'obsolete' => 'R&D Design "'.$design->name.'" is inactive or archived.',
                                        default => 'R&D Design "'.$design->name.'" cannot be used (status: '.$design->status.').',
                                    };

                                    Notification::make()
                                        ->title($title)
                                        ->body($body)
                                        ->warning()
                                        ->send();
                                }
                            }
                        })
                        ->rules([
                            function () {
                                return function (string $attribute, $value, $fail) {
                                    if ($value) {
                                        $design = RdDesign::find($value);
                                        if ($design && $design->status !== 'approved') {
                                            $errorMessage = match ($design->status) {
                                                'draft' => 'Selected R&D design is still in draft status.',
                                                'under_review' => 'Selected R&D design is currently under review.',
                                                'archived', 'obsolete' => 'Selected R&D design is inactive or archived.',
                                                default => 'Selected R&D design cannot be used (status: '.$design->status.').',
                                            };
                                            $fail($errorMessage);
                                        }
                                    }
                                };
                            },
                        ]),
                    Livewire::make(ProjectDesignConsumptionTable::class, fn (callable $get) => [
                        'designId' => $get('design_id'),
                    ])
                        ->key(fn (callable $get) => 'project-design-consumption-table-'.($get('design_id') ?? 'empty'))
                        ->columnSpanFull(),
                    Forms\Components\Select::make('reference_project_id')
                        ->label(new HtmlString('Reference Project <span title="Originating reference project automatically linked when sample or mass project is created via approval" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->relationship('referenceProject', 'project_code')
                        ->getOptionLabelFromRecordUsing(fn ($record) => new HtmlString('
                            <style>
                                .ref-project-link { color: inherit; text-decoration: none; pointer-events: auto !important; cursor: pointer; }
                                .ref-project-link:hover { text-decoration: underline !important; }
                            </style>
                            <a href="'.self::getUrl('edit', ['record' => $record->id]).'" class="ref-project-link">'.$record->project_code.'</a>
                        '))
                        ->allowHtml()
                        ->searchable()
                        ->preload()
                        ->disabled()
                        ->nullable(),
                    Forms\Components\DatePicker::make('start_date'),
                    Forms\Components\DatePicker::make('target_date')
                        ->afterOrEqual('start_date'),
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
                    Forms\Components\Textarea::make('description')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Approval Details')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\DateTimePicker::make('approved_at')
                        ->disabled(),
                    Forms\Components\Select::make('approved_by')
                        ->relationship('approvedByUser', 'name')
                        ->searchable()
                        ->preload()
                        ->disabled(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Project $record): string => self::getUrl('edit', ['record' => $record]))
            ->recordAction(EditAction::class)
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('project_code')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('design.code')
                    ->label('R&D Design')
                    ->sortable()
                    ->searchable()
                    ->html()
                    ->formatStateUsing(function ($state, Project $record) {
                        if (! $state || ! $record->design_id) {
                            return $state ?? '-';
                        }
                        $url = RdDesignResource::getUrl('edit', ['record' => $record->design_id]);
                        $tooltip = $record->design ? "Code: {$record->design->code} (v{$record->design->version})" : '';

                        return '<a href="'.$url.'" title="'.e($tooltip).'" class="hover:underline text-primary-600 dark:text-primary-400 font-medium cursor-pointer" onclick="event.stopPropagation()">'.e($state).'</a>';
                    })
                    ->tooltip(fn (Project $record) => $record->design ? "Code: {$record->design->code} (v{$record->design->version})" : null),
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
                Tables\Columns\TextColumn::make('archive_badge')
                    ->label('')
                    ->state(fn (Project $record) => $record->isArchived() ? 'Archived' : null)
                    ->badge()
                    ->color('warning')
                    ->placeholder(''),
                Tables\Columns\TextColumn::make('customer.name')->searchable(),
                Tables\Columns\TextColumn::make('sub_projects_count')->counts('subProjects')->label('Sub-Projects'),
                Tables\Columns\TextColumn::make('target_qty')
                    ->numeric(decimalPlaces: 0, decimalSeparator: '.', thousandsSeparator: ','),
                Tables\Columns\TextColumn::make('produced_qty')
                    ->numeric(decimalPlaces: 0, decimalSeparator: '.', thousandsSeparator: ','),
            ])
            ->filters([
                SelectFilter::make('visibility')
                    ->label('Visibility')
                    ->options([
                        'archived' => 'Archived',
                        'all' => 'All',
                    ])
                    ->placeholder('Active')
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'archived' => $query->archived(),
                            'all' => $query,
                            default => $query->active(),
                        };
                    }),
                SelectFilter::make('status')->options(['planning' => 'Planning', 'development' => 'Development', 'sampling' => 'Sampling', 'approved' => 'Approved', 'production' => 'Production', 'completed' => 'Completed', 'cancelled' => 'Cancelled']),
                SelectFilter::make('type')->options(['proto' => 'Prototype', 'sample' => 'Sample', 'mass' => 'Mass Production']),
                SelectFilter::make('customer_id')->relationship('customer', 'name'),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => ! $record->isArchived() && $record->status !== 'approved')
                        ->action(function ($record) {
                            try {
                                ProjectTransitionService::approveProject($record, Auth::id() ?? 1);
                                Notification::make()
                                    ->title('Project Approved')
                                    ->success()
                                    ->send();
                            } catch (SubProjectException $e) {
                                Notification::make()
                                    ->title('Sub-Project Review Incomplete')
                                    ->body($e->getMessage())
                                    ->warning()
                                    ->persistent()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation(),
                    Action::make('duplicate')
                        ->label('Duplicate')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('info')
                        ->visible(fn ($record) => ! $record->isArchived())
                        ->action(function ($record) {
                            $copy = ProjectTransitionService::duplicateProject($record);
                            Notification::make()
                                ->title('Project Duplicated: '.$copy->project_code)
                                ->success()
                                ->send();
                        }),
                    Action::make('manage_team')
                        ->label('Team Members')
                        ->icon('heroicon-o-user-group')
                        ->color('info')
                        ->modalHeading(fn ($record) => "Team Members: {$record->name} [{$record->project_code}]")
                        ->modalContent(fn ($record) => view('filament.pages.manage-project-team-modal-wrapper', [
                            'projectId' => $record->id,
                            'isReadOnly' => false,
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->modalWidth('4xl'),
                    EditAction::make(),
                    Action::make('archive')
                        ->label('Archive')
                        ->icon('heroicon-o-archive-box')
                        ->color('secondary')
                        ->visible(function (Project $record): bool {
                            if ($record->isArchived()) {
                                return false;
                            }
                            $user = Auth::user();
                            if (! $user) {
                                return false;
                            }
                            if ($user->isAdmin()) {
                                return true;
                            }

                            return in_array($record->status, ['completed', 'cancelled'], true);
                        })
                        ->form(function (Project $record) {
                            $user = Auth::user();
                            $needsForce = $user?->isAdmin() && (
                                ! in_array($record->status, ['completed', 'cancelled'], true)
                                || ProjectArchiveService::blockers($record) !== []
                            );

                            if (! $needsForce) {
                                return [];
                            }

                            $blockers = ProjectArchiveService::blockers($record);
                            $text = "Force-archive project {$record->project_code}. ";

                            if (! in_array($record->status, ['completed', 'cancelled'], true)) {
                                $text .= 'Project status is not completed/cancelled. ';
                            }

                            if ($blockers !== []) {
                                $text .= 'Blockers:';
                                $html = '<div>'.e($text).'</div><ul class="mt-1 space-y-1" style="list-style-type: disc; padding-left: 1.25rem;">';
                                foreach ($blockers as $b) {
                                    $html .= '<li>'.e($b['label']).'</li>';
                                }
                                $html .= '</ul>';
                            } else {
                                $html = '<div>'.e(trim($text)).'</div>';
                            }

                            return [
                                Forms\Components\Placeholder::make('force_warning')
                                    ->label('Warning')
                                    ->content(new HtmlString($html)),
                                Forms\Components\Checkbox::make('confirm_risk')
                                    ->label('I understand the risks')
                                    ->accepted()
                                    ->required(),
                                Forms\Components\TextInput::make('confirm_code')
                                    ->label('Type project code to confirm')
                                    ->required()
                                    ->rules([
                                        fn () => function (string $attribute, $value, $fail) use ($record) {
                                            if ($value !== $record->project_code) {
                                                $fail('Project code does not match.');
                                            }
                                        },
                                    ]),
                            ];
                        })
                        ->requiresConfirmation(fn (Project $record) => ProjectArchiveService::blockers($record) === []
                            && in_array($record->status, ['completed', 'cancelled'], true))
                        ->action(function (Project $record) {
                            $user = Auth::user();
                            $force = $user?->isAdmin() && (
                                ! in_array($record->status, ['completed', 'cancelled'], true)
                                || ProjectArchiveService::blockers($record) !== []
                            );

                            try {
                                ProjectArchiveService::archive($record, $user, force: $force);
                                Notification::make()
                                    ->title($force ? 'Project force-archived' : 'Project archived')
                                    ->success()
                                    ->send();
                            } catch (ProjectArchiveException $e) {
                                $body = $e->getMessage();
                                if ($e->blockers() !== []) {
                                    $body .= "\n\nBlockers:\n".collect($e->blockers())->pluck('label')->map(fn ($l) => '- '.$l)->implode("\n");
                                }
                                Notification::make()
                                    ->title('Archive failed')
                                    ->body($body)
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Action::make('restore')
                        ->label('Restore')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('success')
                        ->visible(fn (Project $record) => $record->isArchived() && Auth::user()?->isAdmin())
                        ->requiresConfirmation()
                        ->action(function (Project $record) {
                            try {
                                ProjectArchiveService::restore($record, Auth::user());
                                Notification::make()->title('Project restored')->success()->send();
                            } catch (ProjectArchiveException $e) {
                                Notification::make()->title('Restore failed')->body($e->getMessage())->danger()->send();
                            }
                        }),
                    DeleteAction::make()->visible(fn ($record) => ! $record->isArchived()),
                ]),
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
        return [
            SubProjectsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }

    public static function loadBomItems($state, callable $set): void
    {
        if ($state) {
            $bom = Bom::with('items.material.categoryRef')->find($state);
            if ($bom) {
                $items = [];
                foreach ($bom->items as $item) {
                    $categoryName = $item->category
                        ?: $item->material?->categoryRef?->name
                        ?: $item->material?->category
                        ?: '-';

                    $items[] = [
                        'material_name' => $item->material?->name ?? 'N/A',
                        'category' => $categoryName,
                        'quantity_per_unit' => $item->quantity_per_unit,
                        'unit' => $item->unit ?? $item->material?->uom ?? 'pcs',
                        'wastage_percent' => $item->wastage_percent,
                    ];
                }
                $set('bom_items', $items);

                return;
            }
        }
        $set('bom_items', []);
    }
}
