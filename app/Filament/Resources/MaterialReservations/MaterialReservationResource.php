<?php

namespace App\Filament\Resources\MaterialReservations;

use App\Filament\Resources\MaterialReservations\Pages\CreateMaterialReservation;
use App\Filament\Resources\MaterialReservations\Pages\EditMaterialReservation;
use App\Filament\Resources\MaterialReservations\Pages\ListMaterialReservations;
use App\Filament\Resources\MaterialReservations\Schemas\MaterialReservationForm;
use App\Filament\Resources\MaterialReservations\Tables\MaterialReservationsTable;
use App\Models\MaterialReservation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MaterialReservationResource extends Resource
{
    protected static ?string $model = MaterialReservation::class;

    protected static \UnitEnum|string|null $navigationGroup = 'Inventory & Subcon';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Material Reservations';

    protected static ?string $modelLabel = 'Material Reservation';

    protected static ?string $pluralModelLabel = 'Material Reservations';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return MaterialReservationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaterialReservationsTable::configure($table);
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
            'index' => ListMaterialReservations::route('/'),
            'create' => CreateMaterialReservation::route('/create'),
            'edit' => EditMaterialReservation::route('/{record}/edit'),
        ];
    }
}
