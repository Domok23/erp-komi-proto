<?php

namespace App\Filament\Resources\GeneralLedgers;

use App\Filament\Resources\GeneralLedgers\Pages\CreateGeneralLedger;
use App\Filament\Resources\GeneralLedgers\Pages\EditGeneralLedger;
use App\Filament\Resources\GeneralLedgers\Pages\ListGeneralLedgers;
use App\Filament\Resources\GeneralLedgers\Schemas\GeneralLedgerForm;
use App\Filament\Resources\GeneralLedgers\Tables\GeneralLedgersTable;
use App\Models\GeneralLedger;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GeneralLedgerResource extends Resource
{
    protected static ?string $model = GeneralLedger::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'General Ledger';

    protected static ?string $modelLabel = 'General Ledger Entry';

    protected static ?string $pluralModelLabel = 'General Ledger Entries';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return 'Finance & Invoices';
    }

    public static function form(Schema $schema): Schema
    {
        return GeneralLedgerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GeneralLedgersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGeneralLedgers::route('/'),
            'create' => CreateGeneralLedger::route('/create'),
            'edit' => EditGeneralLedger::route('/{record}/edit'),
        ];
    }
}
