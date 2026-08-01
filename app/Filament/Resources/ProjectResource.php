<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers\ProjectEmployeePlacementsRelationManager;
use App\Models\Bom;
use App\Models\Project;
use App\Models\RdDesign;
use App\Services\CodeGenerator;
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
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationLabel = 'Projects';

    protected static ?string $modelLabel = 'Project';

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
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Forms\Components\Select::make('sales_order_id')
                        ->relationship('salesOrder', 'so_number')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Forms\Components\Select::make('design_id')
                        ->relationship('design', 'name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->status === 'approved'
                            ? $record->name
                            : new HtmlString("{$record->name} <span style='color: #888; font-size: 0.9em; margin-left: 5px;'>[{$record->status}]</span>"))
                        ->allowHtml()
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $design = RdDesign::find($state);
                                if ($design && $design->status !== 'approved') {
                                    $set('design_id', null);
                                    $set('bom_id', null);

                                    $title = match ($design->status) {
                                        'draft' => 'Desain Masih Draft',
                                        'archived' => 'Desain Telah Diarsip',
                                        default => 'Desain R&D Tidak Valid',
                                    };

                                    $body = match ($design->status) {
                                        'draft' => 'Desain R&D "'.$design->name.'" masih berstatus draft and belum disetujui.',
                                        'archived' => 'Desain R&D "'.$design->name.'" sudah diarsip.',
                                        default => 'Desain R&D "'.$design->name.'" tidak dapat digunakan (status: '.$design->status.').',
                                    };

                                    Notification::make()
                                        ->title($title)
                                        ->body($body)
                                        ->warning()
                                        ->send();

                                    return;
                                }
                            }
                            $set('bom_id', null);
                        })
                        ->rules([
                            function () {
                                return function (string $attribute, $value, $fail) {
                                    if ($value) {
                                        $design = RdDesign::find($value);
                                        if ($design && $design->status !== 'approved') {
                                            $errorMessage = match ($design->status) {
                                                'draft' => 'Desain R&D terpilih masih berstatus draft.',
                                                'archived' => 'Desain R&D terpilih sudah diarsip.',
                                                default => 'Desain R&D terpilih tidak dapat digunakan (status: '.$design->status.').',
                                            };
                                            $fail($errorMessage);
                                        }
                                    }
                                };
                            },
                        ]),
                    Forms\Components\Select::make('bom_id')
                        ->relationship('bom', 'name', function ($query, callable $get) {
                            $designId = $get('design_id');
                            if ($designId) {
                                return $query->where('design_id', $designId);
                            }

                            return $query;
                        })
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->status === 'active'
                            ? new HtmlString("{$record->name} <span class='bom-number-prefix'>[{$record->bom_number}]</span>")
                            : new HtmlString("{$record->name} <span class='bom-number-prefix'>[{$record->bom_number}] [{$record->status}]</span>"))
                        ->allowHtml()
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->disabled(fn (callable $get) => empty($get('design_id')))
                        ->live()
                        ->afterStateHydrated(function ($state, callable $set) {
                            self::loadBomItems($state, $set);
                        })
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $bom = Bom::find($state);
                                if ($bom && $bom->status !== 'active') {
                                    $set('bom_id', null);
                                    $set('bom_items', []);

                                    $title = match ($bom->status) {
                                        'draft' => 'BOM Masih Draft',
                                        'discontinued' => 'BOM Telah Discontinue',
                                        default => 'BOM Tidak Valid',
                                    };

                                    $body = match ($bom->status) {
                                        'draft' => 'BOM "'.$bom->name.'" masih berstatus draft and belum aktif.',
                                        'archived', 'discontinued' => 'BOM "'.$bom->name.'" sudah tidak digunakan lagi (discontinued).',
                                        default => 'BOM "'.$bom->name.'" tidak dapat digunakan (status: '.$bom->status.').',
                                    };

                                    Notification::make()
                                        ->title($title)
                                        ->body($body)
                                        ->warning()
                                        ->send();
                                } else {
                                    self::loadBomItems($state, $set);
                                }
                            } else {
                                $set('bom_items', []);
                            }
                        })
                        ->rules([
                            function () {
                                return function (string $attribute, $value, $fail) {
                                    if ($value) {
                                        $bom = Bom::find($value);
                                        if ($bom && $bom->status !== 'active') {
                                            $errorMessage = match ($bom->status) {
                                                'draft' => 'BOM terpilih masih berstatus draft.',
                                                'archived', 'discontinued' => 'BOM terpilih sudah discontinue.',
                                                default => 'BOM terpilih tidak dapat digunakan (status: '.$bom->status.').',
                                            };
                                            $fail($errorMessage);
                                        }
                                    }
                                };
                            },
                        ]),
                    Forms\Components\Placeholder::make('no_bom_items')
                        ->label('BOM Items')
                        ->content('Select a BOM to view its items')
                        ->visible(fn (callable $get) => ! $get('bom_id'))
                        ->columnSpanFull(),
                    Forms\Components\Repeater::make('bom_items')
                        ->label('BOM Items')
                        ->dehydrated(false)
                        ->schema([
                            Forms\Components\TextInput::make('material_name')
                                ->label('Material Name')
                                ->disabled(),
                            Forms\Components\TextInput::make('category')
                                ->label('Category')
                                ->disabled(),
                            Forms\Components\TextInput::make('quantity_per_unit')
                                ->label('Quantity Per Unit')
                                ->disabled()
                                ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 4, '.', ',') : $state),
                            Forms\Components\TextInput::make('unit')
                                ->label('Unit')
                                ->disabled(),
                            Forms\Components\TextInput::make('wastage_percent')
                                ->label('Wastage (%)')
                                ->disabled()
                                ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', ',') : $state),
                        ])
                        ->columns(5)
                        ->itemLabel(fn (array $state): ?string => $state['material_name'] ?? null)
                        ->reorderable(false)
                        ->addable(false)
                        ->deletable(false)
                        ->default([])
                        ->visible(fn (callable $get) => $get('bom_id'))
                        ->columnSpanFull(),
                    Forms\Components\Select::make('reference_project_id')
                        ->label(new HtmlString('Reference Project <span title="Proyek asal (referensi) yang otomatis terisi ketika proyek sampel/massal dibuat melalui approval" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
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
                        ->integer()
                        ->default(0)
                        ->minValue(0),
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
            Tables\Columns\TextColumn::make('target_qty')
                ->numeric(decimalPlaces: 0, decimalSeparator: '.', thousandsSeparator: ','),
            Tables\Columns\TextColumn::make('produced_qty')
                ->numeric(decimalPlaces: 0, decimalSeparator: '.', thousandsSeparator: ','),
        ])
            ->filters([
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
                    DeleteAction::make(),
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

    public static function loadBomItems($state, callable $set): void
    {
        if ($state) {
            $bom = Bom::with('items.material')->find($state);
            if ($bom) {
                $items = [];
                foreach ($bom->items as $item) {
                    $items[] = [
                        'material_name' => $item->material?->name ?? 'N/A',
                        'category' => $item->category,
                        'quantity_per_unit' => $item->quantity_per_unit,
                        'unit' => $item->unit,
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
