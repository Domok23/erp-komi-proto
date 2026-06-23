<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseTrackingResource\Pages;
use App\Models\PurchaseTracking;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Filters\SelectFilter;

class PurchaseTrackingResource extends Resource
{
    protected static ?string $model = PurchaseTracking::class;

    protected static ?string $navigationLabel = 'Purchase Tracking';
    protected static ?string $modelLabel = 'Purchase Tracking';
    protected static ?string $pluralModelLabel = 'Purchase Trackings';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('po_type')
                ->options([
                    'supplier' => 'Supplier PO',
                    'subcon' => 'Subcon PO',
                ])
                ->required()
                ->reactive(),
            Forms\Components\Select::make('po_id')
                ->label('Purchase Order')
                ->options(function (callable $get) {
                    $type = $get('po_type');
                    if ($type === 'supplier') {
                        return \App\Models\PoSupplier::pluck('po_number', 'id');
                    } elseif ($type === 'subcon') {
                        return \App\Models\PoSubcon::pluck('po_number', 'id');
                    }
                    return [];
                })
                ->required(),
            Forms\Components\Select::make('tracking_status')
                ->options([
                    'pending' => 'Pending',
                    'shipped' => 'Shipped',
                    'customs' => 'Customs Clearance',
                    'delivered' => 'Delivered',
                    'delayed' => 'Delayed',
                ])
                ->default('pending')
                ->required(),
            Forms\Components\DatePicker::make('estimated_arrival'),
            Forms\Components\DatePicker::make('actual_arrival'),
            Forms\Components\Textarea::make('notes')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('po_type')->badge(),
            Tables\Columns\TextColumn::make('po.po_number')->label('PO Number')->sortable()->searchable(),
            Tables\Columns\BadgeColumn::make('tracking_status')
                ->color(fn (string $state): string => match ($state) {
                    'pending' => 'gray',
                    'shipped' => 'info',
                    'customs' => 'warning',
                    'delivered' => 'success',
                    'delayed' => 'danger',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('estimated_arrival')->date()->sortable(),
            Tables\Columns\TextColumn::make('actual_arrival')->date(),
        ])
            ->filters([
                SelectFilter::make('tracking_status')->options([
                    'pending' => 'Pending',
                    'shipped' => 'Shipped',
                    'customs' => 'Customs Clearance',
                    'delivered' => 'Delivered',
                    'delayed' => 'Delayed',
                ]),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-map-pin';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Procurement';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseTrackings::route('/'),
            'create' => Pages\CreatePurchaseTracking::route('/create'),
            'edit' => Pages\EditPurchaseTracking::route('/{record}/edit'),
        ];
    }
}
