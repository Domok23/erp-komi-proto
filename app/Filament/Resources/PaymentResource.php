<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\InvoicePurchase;
use App\Models\InvoiceSales;
use App\Models\Payment;
use App\Services\CodeGenerator;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationLabel = 'Payments';

    protected static ?string $modelLabel = 'Payment';

    protected static ?string $pluralModelLabel = 'Payments';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Payment Details')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('payment_number')
                        ->default(fn () => CodeGenerator::generatePaymentNumber('sales'))
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->maxLength(50),
                    Forms\Components\Select::make('invoice_type')
                        ->options([
                            'purchase' => 'Purchase Invoice',
                            'sales' => 'Sales Invoice',
                        ])
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $set('payment_number', CodeGenerator::generatePaymentNumber($state));
                            } else {
                                $set('payment_number', null);
                            }
                            $set('invoice_id', null);
                        }),
                    Forms\Components\Select::make('invoice_id')
                        ->label('Invoice')
                        ->options(function (callable $get) {
                            $type = $get('invoice_type');
                            if ($type === 'purchase') {
                                return InvoicePurchase::all()->mapWithKeys(function ($inv) {
                                    $url = InvoicePurchaseResource::getUrl('edit', ['record' => $inv]);

                                    return [$inv->id => '<a href="'.$url.'" class="ref-link">'.$inv->invoice_number.'</a>'];
                                })->toArray();
                            } elseif ($type === 'sales') {
                                return InvoiceSales::all()->mapWithKeys(function ($inv) {
                                    $url = InvoiceSalesResource::getUrl('edit', ['record' => $inv]);

                                    return [$inv->id => '<a href="'.$url.'" class="ref-link">'.$inv->invoice_number.'</a>'];
                                })->toArray();
                            }

                            return [];
                        })
                        ->getOptionLabelUsing(function ($value, callable $get) {
                            if (! $value) {
                                return null;
                            }
                            $type = $get('invoice_type');
                            if ($type === 'purchase') {
                                $inv = InvoicePurchase::find($value);
                                if ($inv) {
                                    $url = InvoicePurchaseResource::getUrl('edit', ['record' => $inv]);

                                    return new HtmlString('<a href="'.$url.'" class="ref-link">'.$inv->invoice_number.'</a>');
                                }
                            } elseif ($type === 'sales') {
                                $inv = InvoiceSales::find($value);
                                if ($inv) {
                                    $url = InvoiceSalesResource::getUrl('edit', ['record' => $inv]);

                                    return new HtmlString('<a href="'.$url.'" class="ref-link">'.$inv->invoice_number.'</a>');
                                }
                            }

                            return $value;
                        })
                        ->allowHtml()
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\DatePicker::make('payment_date')
                        ->default(now()->toDateString())
                        ->required(),
                    Forms\Components\TextInput::make('amount')
                        ->numeric()
                        ->step(0.01)
                        ->required()
                        ->prefix('IDR'),
                    Forms\Components\Select::make('payment_method')
                        ->options([
                            'bank_transfer' => 'Bank Transfer',
                            'cash' => 'Cash',
                            'check' => 'Check',
                            'credit' => 'Credit',
                        ])
                        ->default('bank_transfer')
                        ->required(),
                    Forms\Components\TextInput::make('reference_number')
                        ->maxLength(100),
                    Forms\Components\Textarea::make('notes')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('invoice'))
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('payment_number')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('invoice_type')->badge(),
                Tables\Columns\TextColumn::make('invoice.invoice_number')->label('Invoice Number'),
                Tables\Columns\TextColumn::make('payment_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method'),
            ])
            ->filters([
                SelectFilter::make('payment_method')->options([
                    'bank_transfer' => 'Bank Transfer',
                    'cash' => 'Cash',
                    'check' => 'Check',
                    'credit' => 'Credit',
                ]),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-credit-card';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Finance & Invoices';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
