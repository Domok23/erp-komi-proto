<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PoSubconResource\Pages;
use App\Models\Component;
use App\Models\PoSubcon;
use App\Models\Project;
use App\Models\SubProject;
use App\Services\CodeGenerator;
use App\Services\CompanyContext;
use App\Services\InvoiceGeneratorService;
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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class PoSubconResource extends Resource
{
    protected static ?string $model = PoSubcon::class;

    protected static ?string $navigationLabel = 'PO Subcons';

    protected static ?string $modelLabel = 'PO Subcon';

    protected static ?string $pluralModelLabel = 'PO Subcons';

    protected static ?string $recordTitleAttribute = 'po_number';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('PO Subcon Details')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('po_number')
                        ->default(fn () => CodeGenerator::generatePOSubconNo())
                        ->disabled()
                        ->dehydrated()
                        ->required(),
                    Forms\Components\Select::make('project_ids')
                        ->label('Projects')
                        ->multiple()
                        ->options(fn () => Project::active()->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (is_array($state) && count($state) > 0) {
                                $set('project_id', $state[0]);
                            } else {
                                $set('project_id', null);
                            }
                        }),
                    Forms\Components\Hidden::make('project_id')->dehydrated(),
                    Forms\Components\Select::make('subcon_id')
                        ->relationship('subcon', 'name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => new HtmlString('<a href="'.SubconResource::getUrl('edit', ['record' => $record]).'" class="ref-link">'.$record->name.'</a>'))
                        ->allowHtml()
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\DatePicker::make('po_date')
                        ->default(now()->toDateString())
                        ->required(),
                    Forms\Components\DatePicker::make('delivery_date'),
                    Forms\Components\Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'ordered' => 'Ordered',
                            'partial' => 'Partial',
                            'received' => 'Received',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('draft')
                        ->required(),
                    Forms\Components\Textarea::make('notes')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Subcon Costs')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('service_cost')
                        ->label(new HtmlString('Service Cost <span title="Total biaya jasa subkon yang dihitung otomatis dari akumulasi tabel PO Items di bawah" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                        ->default(0)
                        ->prefix('IDR')
                        ->disabled()
                        ->dehydrated()
                        ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', ',') : $state)
                        ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),
                    Forms\Components\TextInput::make('shipping_cost')
                        ->numeric()
                        ->step(0.01)
                        ->minValue(0)
                        ->default(0)
                        ->prefix('IDR')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set)),
                    Forms\Components\TextInput::make('shipping_return_cost')
                        ->numeric()
                        ->step(0.01)
                        ->minValue(0)
                        ->default(0)
                        ->prefix('IDR')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set)),
                    Forms\Components\TextInput::make('total_cost')
                        ->default(0)
                        ->disabled()
                        ->dehydrated()
                        ->prefix('IDR')
                        ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', ',') : $state)
                        ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),
                ])->columns(2),

            Section::make('PO Items')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Forms\Components\Select::make('allocation_target')
                                ->label(new HtmlString('Sub-Project <span title="Selected sub-project or project allocation for this item" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                                ->options(function (callable $get) {
                                    $projectIds = $get('../../project_ids');
                                    if (empty($projectIds)) {
                                        $legacyId = $get('../../project_id');
                                        if ($legacyId) {
                                            $projectIds = [$legacyId];
                                        } else {
                                            return [];
                                        }
                                    }

                                    if (! is_array($projectIds)) {
                                        $projectIds = [$projectIds];
                                    }

                                    $projects = Project::with('subProjects')->whereIn('id', $projectIds)->get();
                                    $options = [];

                                    foreach ($projects as $project) {
                                        if ($project->hasSubProjects()) {
                                            foreach ($project->subProjects as $sp) {
                                                $options["sp_{$sp->id}"] = "[{$project->name}] {$sp->name}";
                                            }
                                        } else {
                                            $options["proj_{$project->id}"] = "[{$project->name}]";
                                        }
                                    }

                                    return $options;
                                })
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->reactive()
                                ->afterStateHydrated(function ($component, $state, $record) {
                                    if ($record) {
                                        if ($record->sub_project_id) {
                                            $component->state("sp_{$record->sub_project_id}");
                                        } elseif ($record->project_id) {
                                            $component->state("proj_{$record->project_id}");
                                        }
                                    }
                                })
                                ->dehydrated(false)
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if (! $state) {
                                        $set('project_id', null);
                                        $set('sub_project_id', null);

                                        return;
                                    }

                                    if (str_starts_with($state, 'sp_')) {
                                        $spId = (int) str_replace('sp_', '', $state);
                                        $sp = SubProject::find($spId);
                                        $set('sub_project_id', $spId);
                                        $set('project_id', $sp?->project_id);
                                    } elseif (str_starts_with($state, 'proj_')) {
                                        $projId = (int) str_replace('proj_', '', $state);
                                        $set('project_id', $projId);
                                        $set('sub_project_id', null);
                                    }
                                }),
                            Forms\Components\Hidden::make('project_id')->dehydrated(),
                            Forms\Components\Hidden::make('sub_project_id')->dehydrated(),
                            Forms\Components\TextInput::make('description')
                                ->required(),
                            Forms\Components\TextInput::make('qty')
                                ->numeric()
                                ->step(0.01)
                                ->default(1)
                                ->required()
                                ->minValue(0.01)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $qty = floatval($state);
                                    $price = floatval($get('unit_price'));
                                    $set('total_price', number_format($qty * $price, 2, '.', ','));
                                }),
                            Forms\Components\TextInput::make('unit_price')
                                ->numeric()
                                ->step(0.01)
                                ->default(0)
                                ->prefix('IDR')
                                ->required()
                                ->minValue(0.01)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $price = floatval($state);
                                    $qty = floatval($get('qty'));
                                    $set('total_price', number_format($qty * $price, 2, '.', ','));
                                }),
                            Forms\Components\TextInput::make('total_price')
                                ->default(0)
                                ->disabled()
                                ->dehydrated()
                                ->prefix('IDR')
                                ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', ',') : $state)
                                ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),
                            Forms\Components\Select::make('component')
                                ->label('Component')
                                ->options(function ($state) {
                                    $companyId = CompanyContext::getCompanyId();

                                    $options = Component::where('company_id', $companyId)
                                        ->pluck('name', 'name')
                                        ->toArray();

                                    if ($state && ! isset($options[$state])) {
                                        $options[$state] = $state;
                                    }

                                    return $options;
                                })
                                ->searchable()
                                ->preload()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Component Name')
                                        ->required(),
                                ])
                                ->createOptionUsing(function (array $data): string {
                                    $companyId = CompanyContext::getCompanyId();
                                    $comp = Component::firstOrCreate([
                                        'company_id' => $companyId,
                                        'name' => trim($data['name']),
                                    ]);

                                    return $comp->name;
                                })
                                ->createOptionModalHeading('Add New Component')
                                ->nullable()
                                ->dehydrated(),
                        ])
                        ->columns(3)
                        ->columnSpanFull()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $subtotal = 0;
                            foreach ($state as $item) {
                                $subtotal += floatval(str_replace(',', '', $item['total_price'] ?? 0));
                            }
                            $set('service_cost', number_format($subtotal, 2, '.', ','));

                            $shipping = floatval(str_replace(',', '', $get('shipping_cost') ?? 0));
                            $shippingReturn = floatval(str_replace(',', '', $get('shipping_return_cost') ?? 0));
                            $set('total_cost', number_format($subtotal + $shipping + $shippingReturn, 2, '.', ','));
                        }),
                ]),
        ]);
    }

    protected static function recalculateTotals(Get $get, Set $set): void
    {
        $service = floatval(str_replace(',', '', $get('service_cost') ?? 0));
        $shipping = floatval(str_replace(',', '', $get('shipping_cost') ?? 0));
        $shippingReturn = floatval(str_replace(',', '', $get('shipping_return_cost') ?? 0));
        $set('total_cost', number_format($service + $shipping + $shippingReturn, 2, '.', ','));
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('po_number')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('project.name')->label('Project')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('subcon.name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('po_date')->date()->sortable(),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'draft' => 'gray',
                    'ordered' => 'info',
                    'partial' => 'warning',
                    'received' => 'success',
                    'cancelled' => 'danger',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('total_cost')
                ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                ->sortable(),
        ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'ordered' => 'Ordered',
                    'partial' => 'Partial',
                    'received' => 'Received',
                    'cancelled' => 'Cancelled',
                ]),
                SelectFilter::make('subcon_id')->relationship('subcon', 'name'),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('generateInvoice')
                        ->label('Generate Invoice')
                        ->icon('heroicon-o-document-text')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === 'ordered' || $record->status === 'received')
                        ->action(function ($record) {
                            InvoiceGeneratorService::generateFromSubconPO($record);
                            Notification::make()
                                ->title('Subcon Invoice generated successfully!')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-briefcase';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Procurement';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPoSubcons::route('/'),
            'create' => Pages\CreatePoSubcon::route('/create'),
            'edit' => Pages\EditPoSubcon::route('/{record}/edit'),
        ];
    }
}
