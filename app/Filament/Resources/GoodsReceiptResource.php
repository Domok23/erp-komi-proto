<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GoodsReceiptResource\Pages;
use App\Models\GoodsReceipt;
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

class GoodsReceiptResource extends Resource
{
    protected static ?string $model = GoodsReceipt::class;



    protected static ?string $navigationLabel = 'Goods Receipt';
    protected static ?string $modelLabel = 'Goods Receipt';
    protected static ?string $pluralModelLabel = 'Goods Receipts';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
                        Forms\Components\TextInput::make('gr_number')
                ->maxLength(50),
            Forms\Components\Select::make('purchase_order_id')
                ->relationship('purchaseOrder', 'po_number')
                ->nullable(),
            Forms\Components\DatePicker::make('receipt_date'),
            Forms\Components\Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'received' => 'Received',
                    'checked' => 'Checked',
                    'stored' => 'Stored',
                    'rejected' => 'Rejected',
                ])
                ->default('draft'),
            Forms\Components\Select::make('warehouse_type')
                ->options([
                    'main' => 'Main Warehouse',
                    'branch' => 'Branch',
                    'subcon' => 'Subcontractor',
                ]),
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
            Tables\Columns\TextColumn::make('gr_number')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('purchaseOrder.po_number')->searchable(),
            Tables\Columns\TextColumn::make('receipt_date')->date()->sortable(),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'draft' => 'gray',
                    'received' => 'info',
                    'checked' => 'warning',
                    'stored' => 'success',
                    'rejected' => 'danger',
                    default => 'gray',
                }),
            Tables\Columns\BadgeColumn::make('warehouse_type')
                ->colors([
                    'primary' => 'main',
                    'warning' => 'branch',
                    'info' => 'subcon',
                ]),
            Tables\Columns\TextColumn::make('received_by'),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('status')->options(['draft' => 'Draft', 'received' => 'Received', 'checked' => 'Checked', 'stored' => 'Stored', 'rejected' => 'Rejected']),
                SelectFilter::make('warehouse_type')->options(['main' => 'Main Warehouse', 'branch' => 'Branch', 'subcon' => 'Subcontractor']),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }



    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-inbox-arrow-down';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Procurement';
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGoodsReceipts::route('/'),
            'create' => Pages\CreateGoodsReceipt::route('/create'),
            'edit' => Pages\EditGoodsReceipt::route('/{record}/edit'),
        ];
    }
}
