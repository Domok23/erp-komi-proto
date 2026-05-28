<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseReceiptResource\Pages;
use App\Models\PurchaseReceipt;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Filters\SelectFilter;

class PurchaseReceiptResource extends Resource
{
    protected static ?string $model = PurchaseReceipt::class;



    protected static ?string $navigationLabel = 'Purchase Receipt';
    protected static ?string $modelLabel = 'Purchase Receipt';
    protected static ?string $pluralModelLabel = 'Purchase Receipts';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
                        Forms\Components\TextInput::make('pr_number')
                ->maxLength(50),
            Forms\Components\Select::make('purchase_order_id')
                ->relationship('purchaseOrder', 'po_number')
                ->nullable(),
            Forms\Components\DatePicker::make('receipt_date'),
            Forms\Components\Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'received' => 'Received',
                    'inspected' => 'Inspected',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ])
                ->default('draft'),
            Forms\Components\Textarea::make('notes')
                ->maxLength(65535)
                ->columnSpanFull(),
            Forms\Components\TextInput::make('received_by')
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('pr_number')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('purchaseOrder.po_number')->searchable(),
            Tables\Columns\TextColumn::make('receipt_date')->date()->sortable(),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'draft' => 'gray',
                    'received' => 'info',
                    'inspected' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('received_by'),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('status')->options(['draft' => 'Draft', 'received' => 'Received', 'inspected' => 'Inspected', 'approved' => 'Approved', 'rejected' => 'Rejected']),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }



    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-clipboard-document-check';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Procurement';
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseReceipts::route('/'),
            'create' => Pages\CreatePurchaseReceipt::route('/create'),
            'edit' => Pages\EditPurchaseReceipt::route('/{record}/edit'),
        ];
    }
}
