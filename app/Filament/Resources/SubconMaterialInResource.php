<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubconMaterialInResource\Pages;
use App\Models\SubconMaterialIn;
use App\Models\Material;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Filters\SelectFilter;

class SubconMaterialInResource extends Resource
{
    protected static ?string $model = SubconMaterialIn::class;

    protected static ?string $navigationLabel = 'Subcon Material In';
    protected static ?string $modelLabel = 'Subcon Material In';
    protected static ?string $pluralModelLabel = 'Subcon Material Ins';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('document_number')
                ->required()
                ->maxLength(50),
            Forms\Components\Select::make('po_subcon_id')
                ->relationship('poSubcon', 'po_number')
                ->searchable()
                ->preload()
                ->nullable()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    $po = \App\Models\PoSubcon::find($state, ['*']);
                    if ($po) {
                        $set('subcon_id', $po->subcon_id);
                    }
                }),
            Forms\Components\Select::make('subcon_id')
                ->relationship('subcon', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\DatePicker::make('receive_date')
                ->default(now()->toDateString())
                ->required(),
            Forms\Components\Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'received' => 'Received',
                    'verified' => 'Verified',
                ])
                ->default('draft')
                ->required(),
            Forms\Components\Textarea::make('notes')
                ->columnSpanFull(),

            Section::make('Received Materials')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Forms\Components\Select::make('material_id')
                                ->relationship('material', 'name')
                                ->getOptionLabelFromRecordUsing(function ($record) {
                                    $companyId = \App\Services\CompanyContext::getCompanyId();
                                    $stock = \App\Models\InventoryStock::where('material_id', $record->id)
                                        ->where('company_id', $companyId)
                                        ->sum('quantity');
                                    return "[{$record->code}] {$record->name} (Stock: " . number_format($stock, 2) . " {$record->unit})";
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $material = Material::find($state, ['*']);
                                    if ($material) {
                                        $set('unit', $material->unit);
                                    }
                                }),
                            Forms\Components\TextInput::make('qty_received')
                                ->numeric()
                                ->default(1)
                                ->required(),
                            Forms\Components\TextInput::make('qty_rejected')
                                ->numeric()
                                ->default(0),
                            Forms\Components\TextInput::make('unit')
                                ->disabled()
                                ->dehydrated()
                                ->default('pcs'),
                        ])
                        ->columns(3)
                        ->columnSpanFull(),
                ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('document_number')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('poSubcon.po_number')->label('Subcon PO'),
            Tables\Columns\TextColumn::make('subcon.name')->sortable(),
            Tables\Columns\TextColumn::make('receive_date')->date()->sortable(),
            Tables\Columns\BadgeColumn::make('status')
                ->color(fn (string $state): string => match ($state) {
                    'draft' => 'gray',
                    'received' => 'info',
                    'verified' => 'success',
                    default => 'gray',
                }),
        ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'received' => 'Received',
                    'verified' => 'Verified',
                ]),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-arrow-down-on-square';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Inventory & Subcon';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubconMaterialIns::route('/'),
            'create' => Pages\CreateSubconMaterialIn::route('/create'),
            'edit' => Pages\EditSubconMaterialIn::route('/{record}/edit'),
        ];
    }
}
