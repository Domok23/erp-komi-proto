<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoicePurchaseResource\Pages;
use App\Models\InvoicePurchase;
use App\Models\Payment;
use App\Models\PoSubcon;
use App\Models\PoSupplier;
use App\Services\CodeGenerator;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InvoicePurchaseResource extends Resource
{
    protected static ?string $model = InvoicePurchase::class;

    protected static ?string $navigationLabel = 'Invoice Purchase';

    protected static ?string $modelLabel = 'Invoice Purchase';

    protected static ?string $pluralModelLabel = 'Invoice Purchases';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('invoice_number')
                ->default(fn () => CodeGenerator::generateInvoicePurchaseNo())
                ->disabled()
                ->dehydrated()
                ->required(),
            Forms\Components\Select::make('purchase_type')
                ->options([
                    'po_supplier' => 'Supplier PO',
                    'po_subcon' => 'Subcon PO',
                ])
                ->required()
                ->reactive()
                ->afterStateUpdated(function (callable $set) {
                    $set('reference_id', null);
                    $set('subtotal', 0);
                    $set('tax_amount', 0);
                    $set('grand_total', 0);
                }),
            Forms\Components\Select::make('reference_id')
                ->label('Purchase Order')
                ->options(function (callable $get) {
                    $type = $get('purchase_type');
                    if ($type === 'po_supplier') {
                        return PoSupplier::pluck('po_number', 'id');
                    } elseif ($type === 'po_subcon') {
                        return PoSubcon::pluck('po_number', 'id');
                    }

                    return [];
                })
                ->searchable()
                ->preload()
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    if (!$state) {
                        $set('subtotal', 0);
                        $set('tax_amount', 0);
                        $set('grand_total', 0);
                        return;
                    }
                    $type = $get('purchase_type');
                    if ($type === 'po_supplier') {
                        $po = PoSupplier::find($state, ['*']);
                        if ($po) {
                            $set('subtotal', $po->subtotal);
                            $set('tax_amount', $po->ppn_amount);
                            $set('grand_total', $po->grand_total);
                        }
                    } elseif ($type === 'po_subcon') {
                        $po = PoSubcon::find($state, ['*']);
                        if ($po) {
                            $set('subtotal', $po->service_cost);
                            $set('tax_amount', 0);
                            $set('grand_total', $po->total_cost);
                        }
                    }
                }),
            Forms\Components\DatePicker::make('invoice_date')
                ->default(now()->toDateString())
                ->required(),
            Forms\Components\DatePicker::make('due_date'),
            Forms\Components\TextInput::make('subtotal')
                ->numeric()
                ->default(0)
                ->prefix('IDR')
                ->required()
                ->reactive()
                ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set)),
            Forms\Components\TextInput::make('tax_amount')
                ->numeric()
                ->default(0)
                ->prefix('IDR')
                ->required()
                ->reactive()
                ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set)),
            Forms\Components\TextInput::make('grand_total')
                ->numeric()
                ->default(0)
                ->prefix('IDR')
                ->disabled()
                ->dehydrated()
                ->required(),
            Forms\Components\TextInput::make('paid_amount')
                ->numeric()
                ->default(0)
                ->prefix('IDR')
                ->required(),
            Forms\Components\Select::make('status')
                ->options([
                    'unpaid' => 'Unpaid',
                    'partial' => 'Partial Paid',
                    'paid' => 'Paid',
                    'overdue' => 'Overdue',
                ])
                ->default('unpaid')
                ->required(),
            Forms\Components\Textarea::make('notes')
                ->columnSpanFull(),
        ]);
    }

    protected static function recalculateTotals(Get $get, Set $set): void
    {
        $subtotal = floatval($get('subtotal') ?? 0);
        $tax = floatval($get('tax_amount') ?? 0);
        $set('grand_total', $subtotal + $tax);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('invoice_number')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('purchase_type')->badge(),
            Tables\Columns\TextColumn::make('invoice_date')->date()->sortable(),
            Tables\Columns\TextColumn::make('grand_total')->numeric()->sortable(),
            Tables\Columns\TextColumn::make('paid_amount')->numeric(),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'unpaid' => 'danger',
                    'partial' => 'warning',
                    'paid' => 'success',
                    'overdue' => 'danger',
                    default => 'gray',
                }),
        ])
            ->filters([
                SelectFilter::make('status')->options([
                    'unpaid' => 'Unpaid',
                    'partial' => 'Partial Paid',
                    'paid' => 'Paid',
                    'overdue' => 'Overdue',
                ]),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    Action::make('pay')
                        ->label('Pay')
                        ->icon('heroicon-o-credit-card')
                        ->color('success')
                        ->visible(fn ($record) => $record->status !== 'paid')
                        ->form([
                            Forms\Components\DatePicker::make('payment_date')
                                ->default(now()->toDateString())
                                ->required(),
                            Forms\Components\TextInput::make('amount')
                                ->numeric()
                                ->required()
                                ->default(fn ($record) => $record->grand_total - $record->paid_amount),
                            Forms\Components\Select::make('payment_method')
                                ->options([
                                    'bank_transfer' => 'Bank Transfer',
                                    'cash' => 'Cash',
                                    'check' => 'Check',
                                    'credit' => 'Credit',
                                ])
                                ->default('bank_transfer')
                                ->required(),
                            Forms\Components\TextInput::make('reference_number'),
                            Forms\Components\Textarea::make('notes'),
                        ])
                        ->action(function ($record, array $data) {
                            $paymentCount = Payment::count('*') + 1;
                            Payment::create([
                                'company_id' => $record->company_id,
                                'invoice_type' => 'purchase',
                                'invoice_id' => $record->id,
                                'payment_number' => 'PAY-PUR-'.now()->year.'-'.sprintf('%03d', $paymentCount),
                                'payment_date' => $data['payment_date'],
                                'amount' => $data['amount'],
                                'payment_method' => $data['payment_method'],
                                'reference_number' => $data['reference_number'],
                                'notes' => $data['notes'],
                            ]);

                            $newPaidAmount = $record->paid_amount + $data['amount'];
                            $newStatus = 'partial';
                            if ($newPaidAmount >= $record->grand_total) {
                                $newStatus = 'paid';
                            }

                            $record->update([
                                'paid_amount' => $newPaidAmount,
                                'status' => $newStatus,
                            ]);

                            Notification::make()
                                ->title('Payment recorded successfully!')
                                ->success()
                                ->send();
                        }),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Finance & Invoices';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoicePurchases::route('/'),
            'create' => Pages\CreateInvoicePurchase::route('/create'),
            'edit' => Pages\EditInvoicePurchase::route('/{record}/edit'),
        ];
    }
}
