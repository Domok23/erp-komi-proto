<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialResource\Pages;
use App\Filament\Resources\MaterialResource\RelationManagers;
use App\Models\Material;
use App\Models\Supplier;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;
use OpenSpout\Common\Entity\Row;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;
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

class MaterialResource extends Resource
{
    protected static ?string $model = Material::class;

    protected static ?string $navigationLabel = 'Material';

    protected static ?string $modelLabel = 'Material';

    protected static ?string $pluralModelLabel = 'Material';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Material Details')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(50),
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('category')
                        ->options([
                            'fabric' => 'Fabric',
                            'zipper' => 'Zipper',
                            'button' => 'Button',
                            'thread' => 'Thread',
                            'handle' => 'Handle',
                            'label' => 'Label',
                            'interlining' => 'Interlining',
                            'semi_finished' => 'Semi-Finished Product',
                            'finished' => 'Finished Product',
                            'other' => 'Other',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('unit')
                        ->default('pcs')
                        ->maxLength(20),
                    Forms\Components\TextInput::make('stock')
                        ->numeric()
                        ->step(0.01)
                        ->default(0),
                    Forms\Components\TextInput::make('min_stock')
                        ->numeric()
                        ->step(0.01)
                        ->default(0),
                    Forms\Components\TextInput::make('price')
                        ->numeric()
                        ->step(0.01)
                        ->prefix('$')
                        ->default(0),
                    Forms\Components\Select::make('supplier_id')
                        ->relationship('supplier', 'name')
                        ->searchable()
                        ->preload(),
                    Forms\Components\Textarea::make('description')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('is_active')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('code')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
            Tables\Columns\BadgeColumn::make('category'),
            Tables\Columns\TextColumn::make('unit'),
            Tables\Columns\TextColumn::make('stock')
                ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                ->sortable(),
            Tables\Columns\TextColumn::make('min_stock')
                ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
            Tables\Columns\TextColumn::make('price')->numeric()->money('USD')->sortable(),
            Tables\Columns\TextColumn::make('supplier.name')->searchable(),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('category')->options([
                    'fabric' => 'Fabric',
                    'zipper' => 'Zipper',
                    'button' => 'Button',
                    'thread' => 'Thread',
                    'handle' => 'Handle',
                    'label' => 'Label',
                    'interlining' => 'Interlining',
                    'semi_finished' => 'Semi-Finished Product',
                    'finished' => 'Finished Product',
                    'other' => 'Other',
                ]),
                SelectFilter::make('is_active')->options(['1' => 'Active', '0' => 'Inactive']),
                SelectFilter::make('supplier_id')->relationship('supplier', 'name'),
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
        return 'heroicon-o-cube';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Master Data';
    }

    public static function getNavigationSort(): ?int
    {
        return 6;
    }

    public static function normalizeCategory(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $cleaned = strtolower(trim(strval($value)));

        $validCategories = [
            'fabric' => ['fabric'],
            'zipper' => ['zipper'],
            'button' => ['button'],
            'thread' => ['thread'],
            'handle' => ['handle'],
            'label' => ['label'],
            'interlining' => ['interlining'],
            'semi_finished' => ['semi-finished product', 'semi_finished', 'semi finished', 'semi-finished', 'semi finished product'],
            'finished' => ['finished product', 'finished', 'finished_product'],
            'other' => ['other'],
        ];

        foreach ($validCategories as $key => $mappings) {
            if ($cleaned === $key || in_array($cleaned, $mappings)) {
                return $key;
            }
        }

        return null;
    }

    public static function normalizeDecimal(mixed $value): ?float
    {
        if (blank($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $state = str_replace(' ', '', trim(strval($value)));

        if (preg_match('/^\d{1,3}(\.\d{3})+,\d+$/', $state)) {
            $state = str_replace('.', '', $state);
            $state = str_replace(',', '.', $state);
        } elseif (preg_match('/^\d{1,3}(,\d{3})+\.\d+$/', $state)) {
            $state = str_replace(',', '', $state);
        } elseif (str_contains($state, ',') && !str_contains($state, '.')) {
            $parts = explode(',', $state);
            if (count($parts) === 2 && strlen($parts[1]) === 3 && (int)$parts[0] > 0) {
                $state = str_replace(',', '', $state);
            } else {
                $state = str_replace(',', '.', $state);
            }
        }

        return is_numeric($state) ? (float) $state : null;
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\InventoryStocksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaterials::route('/'),
            'create' => Pages\CreateMaterial::route('/create'),
            'edit' => Pages\EditMaterial::route('/{record}/edit'),
        ];
    }
}
