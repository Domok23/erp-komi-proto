<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceSalesResource\Pages;
use App\Models\InvoiceSales;
use App\Models\Payment;
use App\Models\SalesOrder;
use App\Services\CodeGenerator;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InvoiceSalesResource extends Resource
{
    protected static ?string $model = InvoiceSales::class;

    protected static ?string $navigationLabel = 'Invoice Sales';

    protected static ?string $modelLabel = 'Invoice Sales';

    protected static ?string $pluralModelLabel = 'Invoice Sales';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('invoice_number')
                ->default(fn () => CodeGenerator::generateInvoiceSalesNo())
                ->disabled()
                ->dehydrated()
                ->required(),
            Forms\Components\Select::make('sales_order_id')
                ->relationship('salesOrder', 'so_number')
                ->searchable()
                ->preload()
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    $so = SalesOrder::find($state, ['*']);
                    if ($so) {
                        $set('subtotal', $so->subtotal);
                        $set('ppn_percent', $so->ppn_percent);
                        $set('ppn_amount', $so->ppn_amount);
                        $set('shipping_cost', $so->shipping_cost);
                        $set('grand_total', $so->grand_total);
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
                ->required(),
            Forms\Components\TextInput::make('ppn_percent')
                ->numeric()
                ->default(11)
                ->suffix('%')
                ->required(),
            Forms\Components\TextInput::make('ppn_amount')
                ->numeric()
                ->default(0)
                ->prefix('IDR')
                ->required(),
            Forms\Components\TextInput::make('shipping_cost')
                ->numeric()
                ->default(0)
                ->prefix('IDR')
                ->required(),
            Forms\Components\TextInput::make('grand_total')
                ->numeric()
                ->default(0)
                ->prefix('IDR')
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
            Forms\Components\Toggle::make('is_tax_invoice')
                ->default(false)
                ->inline(false),
            Forms\Components\TextInput::make('tax_invoice_number'),
            Forms\Components\Textarea::make('notes')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('invoice_number')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('salesOrder.so_number')->searchable(),
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
            Tables\Columns\IconColumn::make('is_tax_invoice')->boolean(),
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
                                'invoice_type' => 'sales',
                                'invoice_id' => $record->id,
                                'payment_number' => 'PAY-SALES-'.now()->year.'-'.sprintf('%03d', $paymentCount),
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
        return 'heroicon-o-banknotes';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Finance & Invoices';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoiceSales::route('/'),
            'create' => Pages\CreateInvoiceSales::route('/create'),
            'edit' => Pages\EditInvoiceSales::route('/{record}/edit'),
        ];
    }
}
