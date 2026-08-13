<?php

namespace App\Filament\Resources;

use App\Filament\Actions\StockPreviewAction;
use App\Filament\Resources\PoSupplierResource\Pages;
use App\Models\Component;
use App\Models\InventoryStock;
use App\Models\Material;
use App\Models\PoSupplier;
use App\Models\Project;
use App\Models\SubProject;
use App\Models\User;
use App\Services\CodeGenerator;
use App\Services\CompanyContext;
use App\Services\InvoiceGeneratorService;
use Barryvdh\DomPDF\Facade\Pdf;
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
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

class PoSupplierResource extends Resource
{
    protected static ?string $recordTitleAttribute = 'po_number';

    protected static ?string $model = PoSupplier::class;

    protected static ?string $navigationLabel = 'PO Suppliers';

    protected static ?string $modelLabel = 'PO Supplier';

    protected static ?string $pluralModelLabel = 'PO Suppliers';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Placeholder::make('approval_status')
                ->label('Approval status')
                ->content(fn (?PoSupplier $record) => $record ? new HtmlString(view('filament.components.po-approval-banner', ['record' => $record])->render()) : '')
                ->columnSpanFull(),

            Section::make('PO Supplier Details')
                ->disabled(fn (?PoSupplier $record) => $record && $record->approval_status !== 'draft')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('po_number')
                        ->default(fn () => CodeGenerator::generatePOSupplierNo())
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
                    Forms\Components\Select::make('supplier_id')
                        ->relationship('supplier', 'name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => new HtmlString('<a href="'.SupplierResource::getUrl('edit', ['record' => $record]).'" class="ref-link">'.$record->name.'</a>'))
                        ->allowHtml()
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive(),
                    Forms\Components\DatePicker::make('po_date')
                        ->default(now()->toDateString())
                        ->required(),
                    Forms\Components\DatePicker::make('delivery_date')
                        ->afterOrEqual('po_date'),
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

            Section::make('Cost & Tax Totals')
                ->disabled(fn (?PoSupplier $record) => $record && $record->approval_status !== 'draft')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('subtotal')
                        ->default(0)
                        ->disabled()
                        ->dehydrated()
                        ->prefix('IDR')
                        ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', ',') : $state)
                        ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),
                    Forms\Components\TextInput::make('ppn_percent')
                        ->label('PPN (%)')
                        ->numeric()
                        ->step(0.01)
                        ->minValue(0)
                        ->default(11)
                        ->suffix('%')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($get, $set) => self::recalculateTotals($get, $set)),
                    Forms\Components\TextInput::make('ppn_amount')
                        ->default(0)
                        ->disabled()
                        ->dehydrated()
                        ->prefix('IDR')
                        ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', ',') : $state)
                        ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),
                    Forms\Components\TextInput::make('grand_total')
                        ->default(0)
                        ->disabled()
                        ->dehydrated()
                        ->prefix('IDR')
                        ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', ',') : $state)
                        ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),
                ])->columns(2),

            Section::make('PO Items')
                ->disabled(fn (?PoSupplier $record) => $record && $record->approval_status !== 'draft')
                ->columnSpanFull()
                ->headerActions([
                    StockPreviewAction::make('form'),
                ])
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
                            Forms\Components\Select::make('material_id')
                                ->label('Material')
                                ->options(function (callable $get) {
                                    $supplierId = $get('../../supplier_id');
                                    if (! $supplierId) {
                                        return [];
                                    }

                                    return Material::where('supplier_id', $supplierId)
                                        ->pluck('name', 'id');
                                })
                                ->getOptionLabelFromRecordUsing(function ($record) {
                                    $companyId = CompanyContext::getCompanyId();
                                    $stock = InventoryStock::where('material_id', $record->id)
                                        ->where('company_id', $companyId)
                                        ->sum('quantity');

                                    return "[{$record->code}] {$record->name} (Stock: ".number_format($stock, 2)." {$record->unit})";
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $material = $state ? Material::with('uomRef')->find($state) : null;
                                    $set('unit', $material?->uom ?? $material?->uomRef?->name ?? $material?->unit);
                                    $price = $material?->price ?? 0;
                                    $set('unit_price', $price);
                                    $qty = floatval($get('qty') ?? 1);
                                    $set('total_price', number_format($qty * $price, 2, '.', ','));
                                }),
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
                            Forms\Components\TextInput::make('unit')
                                ->label('UOM')
                                ->default('pcs')
                                ->disabled()
                                ->dehydrated(),
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
                            Forms\Components\TextInput::make('qty_received')
                                ->default(0)
                                ->disabled()
                                ->dehydrated()
                                ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format((float) $state, 2, '.', ',') : $state)
                                ->dehydrateStateUsing(fn ($state) => str_replace(',', '', $state)),
                        ])
                        ->columns(3)
                        ->columnSpanFull()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $subtotal = 0;
                            foreach ($state as $item) {
                                $subtotal += floatval(str_replace(',', '', $item['total_price'] ?? 0));
                            }
                            $set('subtotal', number_format($subtotal, 2, '.', ','));

                            $ppnPct = floatval(str_replace(',', '', $get('ppn_percent') ?? 11));
                            $ppnAmount = $subtotal * ($ppnPct / 100);
                            $set('ppn_amount', number_format($ppnAmount, 2, '.', ','));

                            $set('grand_total', number_format($subtotal + $ppnAmount, 2, '.', ','));
                        }),
                ]),
        ]);
    }

    protected static function recalculateTotals(callable $get, callable $set): void
    {
        $subtotal = floatval(str_replace(',', '', $get('subtotal') ?? 0));
        $ppnPct = floatval(str_replace(',', '', $get('ppn_percent') ?? 11));
        $ppnAmount = $subtotal * ($ppnPct / 100);
        $set('ppn_amount', number_format($ppnAmount, 2, '.', ','));
        $set('grand_total', number_format($subtotal + $ppnAmount, 2, '.', ','));
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('po_number')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('project.name')->label('Project')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('supplier.name')->sortable()->searchable(),
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
            Tables\Columns\TextColumn::make('grand_total')
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
                SelectFilter::make('supplier_id')->relationship('supplier', 'name'),
            ])
            ->actions([
                ActionGroup::make([
                    static::getSubmitForApprovalAction(),
                    static::getApproveSignAction(),
                    static::getRejectApprovalAction(),
                    static::getRevisePoAction(),
                    StockPreviewAction::make('table'),
                    Action::make('generateInvoice')
                        ->label('Generate Invoice')
                        ->icon('heroicon-o-document-text')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === 'ordered' || $record->status === 'received')
                        ->action(function ($record) {
                            InvoiceGeneratorService::generateFromPO($record);
                            Notification::make()
                                ->title('Purchase Invoice generated successfully!')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Action::make('downloadPdf')
                        ->label('Download PDF')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function ($record) {
                            $pdf = Pdf::loadView('pdf.po-supplier', [
                                'po' => $record,
                                'company' => $record->company,
                                'supplier' => $record->supplier,
                            ]);

                            return response()->streamDownload(
                                fn () => print ($pdf->output()),
                                "po-{$record->po_number}.pdf"
                            );
                        }),

                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-shopping-cart';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Procurement';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPoSuppliers::route('/'),
            'create' => Pages\CreatePoSupplier::route('/create'),
            'edit' => Pages\EditPoSupplier::route('/{record}/edit'),
        ];
    }

    public static function getSubmitForApprovalAction(): Action
    {
        return Action::make('submit_for_approval')
            ->label('Submit for Approval')
            ->icon('heroicon-o-paper-airplane')
            ->color('primary')
            ->visible(fn (PoSupplier $record) => $record->approval_status === 'draft' && $record->items()->count() > 0)
            ->action(function (PoSupplier $record) {
                $record->update([
                    'approval_status' => 'pending_approval',
                ]);

                $record->approvals()->create([
                    'approval_level' => 'manager',
                    'status' => 'pending',
                ]);

                if (floatval($record->grand_total) >= PoSupplier::DIRECTOR_APPROVAL_THRESHOLD) {
                    $record->approvals()->create([
                        'approval_level' => 'director',
                        'status' => 'pending',
                    ]);
                }

                Notification::make()
                    ->title('PO submitted for authorization!')
                    ->success()
                    ->send();
            })
            ->requiresConfirmation();
    }

    public static function getApproveSignAction(): Action
    {
        return Action::make('approve_sign')
            ->label('Approve & Sign')
            ->icon('heroicon-o-pencil-square')
            ->color('success')
            ->visible(function (PoSupplier $record) {
                if ($record->approval_status !== 'pending_approval') {
                    return false;
                }

                $activeLevel = $record->approvals()->where('status', 'pending')->orderBy('id', 'asc')->first();
                if (! $activeLevel) {
                    return false;
                }

                if ($activeLevel->approval_level === 'director') {
                    $managerApproved = $record->approvals()->where('approval_level', 'manager')->where('status', 'approved')->exists();
                    if (! $managerApproved) {
                        return false;
                    }
                }

                return true;
            })
            ->form(function (PoSupplier $record) {
                $formFields = [];
                /** @var User|null $user */
                $user = Auth::user();

                if ($user && $user->signature) {
                    $formFields[] = Forms\Components\Placeholder::make('saved_signature_preview')
                        ->label('Your Saved Signature')
                        ->content(new HtmlString('<div style="background:#fff; padding:10px; border-radius:8px; border:1px solid #ddd; max-width: 250px;"><img src="'.e($user->signature).'" style="max-height: 80px;" /></div>'));

                    $formFields[] = Forms\Components\Toggle::make('use_saved_signature')
                        ->label('Use my saved profile signature')
                        ->default(true)
                        ->live();
                }

                $formFields[] = SignaturePad::make('drawn_signature')
                    ->label('Draw Signature')
                    ->backgroundColor('rgb(248, 250, 252)')
                    ->exportBackgroundColor('rgb(255, 255, 255)')
                    ->penColor('rgb(15, 23, 42)')
                    ->visible(fn ($get) => ! ($get('use_saved_signature') ?? false))
                    ->required(fn ($get) => ! ($get('use_saved_signature') ?? false));

                if ($user && ! $user->signature) {
                    $formFields[] = Forms\Components\Checkbox::make('save_to_profile')
                        ->label('Save this signature to my profile for future use')
                        ->default(true);
                }

                return $formFields;
            })
            ->action(function (PoSupplier $record, array $data) {
                /** @var User|null $user */
                $user = Auth::user();
                $signature = null;

                if ($data['use_saved_signature'] ?? false) {
                    $signature = $user->signature;
                } else {
                    $signature = $data['drawn_signature'] ?? null;

                    if ($signature && ($data['save_to_profile'] ?? false)) {
                        $user->update(['signature' => $signature]);
                    }
                }

                if (! $signature) {
                    Notification::make()->title('Signature is required!')->danger()->send();

                    return;
                }

                $activeLevel = $record->approvals()->where('status', 'pending')->orderBy('id', 'asc')->first();
                if ($activeLevel) {
                    $activeLevel->update([
                        'status' => 'approved',
                        'user_id' => $user->id,
                        'signature_path' => $signature,
                        'actioned_at' => now(),
                    ]);
                }

                $remainingPending = $record->approvals()->where('status', 'pending')->exists();
                if (! $remainingPending) {
                    $record->update([
                        'approval_status' => 'approved',
                        'status' => 'ordered',
                    ]);
                }

                Notification::make()
                    ->title('Purchase Order approved & signed successfully!')
                    ->success()
                    ->send();
            });
    }

    public static function getRejectApprovalAction(): Action
    {
        return Action::make('reject_approval')
            ->label('Reject')
            ->icon('heroicon-o-x-mark')
            ->color('danger')
            ->visible(function (PoSupplier $record) {
                if ($record->approval_status !== 'pending_approval') {
                    return false;
                }

                $activeLevel = $record->approvals()->where('status', 'pending')->orderBy('id', 'asc')->first();
                if (! $activeLevel) {
                    return false;
                }

                if ($activeLevel->approval_level === 'director') {
                    $managerApproved = $record->approvals()->where('approval_level', 'manager')->where('status', 'approved')->exists();
                    if (! $managerApproved) {
                        return false;
                    }
                }

                return true;
            })
            ->form([
                Forms\Components\Textarea::make('rejection_reason')
                    ->label('Reason for Rejection')
                    ->placeholder('Provide a brief description of why this PO is rejected.')
                    ->required(),
            ])
            ->action(function (PoSupplier $record, array $data) {
                /** @var User|null $user */
                $user = Auth::user();

                $activeLevel = $record->approvals()->where('status', 'pending')->orderBy('id', 'asc')->first();
                if ($activeLevel) {
                    $activeLevel->update([
                        'status' => 'rejected',
                        'user_id' => $user->id,
                        'rejection_reason' => $data['rejection_reason'],
                        'actioned_at' => now(),
                    ]);
                }

                $record->update([
                    'approval_status' => 'rejected',
                ]);

                Notification::make()
                    ->title('PO has been rejected.')
                    ->danger()
                    ->send();
            });
    }

    public static function getRevisePoAction(): Action
    {
        return Action::make('revise_po')
            ->label('Revise')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->visible(fn (PoSupplier $record) => $record->approval_status === 'rejected')
            ->action(function (PoSupplier $record, $livewire) {
                $baseNumber = preg_replace('/-R\d+$/', '', $record->po_number);
                $newRevisionNumber = $record->revision_number + 1;
                $newPoNumber = $baseNumber.'-R'.$newRevisionNumber;

                $newPo = $record->replicate();
                $newPo->po_number = $newPoNumber;
                $newPo->approval_status = 'draft';
                $newPo->status = 'draft';
                $newPo->parent_id = $record->id;
                $newPo->revision_number = $newRevisionNumber;
                $newPo->save();

                foreach ($record->items as $item) {
                    $newItem = $item->replicate();
                    $newItem->po_supplier_id = $newPo->id;
                    $newItem->save();
                }

                Notification::make()
                    ->title("Revision PO Created: {$newPo->po_number}")
                    ->success()
                    ->send();

                return $livewire->redirect(PoSupplierResource::getUrl('edit', ['record' => $newPo]), navigate: true);
            });
    }
}
