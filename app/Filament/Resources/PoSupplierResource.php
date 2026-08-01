<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PoSupplierResource\Pages;
use App\Models\InventoryStock;
use App\Models\Material;
use App\Models\PoSupplier;
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
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

class PoSupplierResource extends Resource
{
    protected static ?string $model = PoSupplier::class;

    protected static ?string $navigationLabel = 'PO Suppliers';

    protected static ?string $modelLabel = 'PO Supplier';

    protected static ?string $pluralModelLabel = 'PO Suppliers';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Placeholder::make('approval_status_banner')
                ->label('')
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
                    Forms\Components\Select::make('project_id')
                        ->relationship('project', 'name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => new HtmlString('<a href="'.ProjectResource::getUrl('edit', ['record' => $record]).'" class="ref-link">'.$record->name.'</a> <span class="project-code-prefix">['.$record->project_code.']</span>'))
                        ->allowHtml()
                        ->searchable()
                        ->preload()
                        ->nullable(),
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
                        ->numeric()
                        ->step(0.01)
                        ->minValue(0)
                        ->default(11)
                        ->suffix('%')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set)),
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
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship('items')
                        ->schema([
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
                                    $material = $state ? Material::find($state, ['*']) : null;
                                    $set('unit', $material?->unit);
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

            Section::make('Management Authorization')
                ->columnSpanFull()
                ->description('Management authorized signature to approve this Purchase Order.')
                ->schema([
                    SignaturePad::make('buyer_signature')
                        ->label('Management Signature')
                        ->hint('Draw signature for management authorization of this PO')
                        ->backgroundColor('rgb(255, 255, 255)')
                        ->penColor('rgb(15, 23, 42)')
                        ->downloadable()
                        ->nullable(),
                ]),
        ]);
    }

    protected static function recalculateTotals(Get $get, Set $set): void
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
                    \Filament\Actions\Action::make('downloadPdf')
                        ->label('Download PDF')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function ($record) {
                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.po-supplier', [
                                'po' => $record,
                                'company' => $record->company,
                                'supplier' => $record->supplier,
                            ]);
                            return response()->streamDownload(
                                fn () => print($pdf->output()),
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
}
