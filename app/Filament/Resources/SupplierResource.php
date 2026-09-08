<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Filament\Resources\SupplierResource\RelationManagers;
use App\Models\Supplier;
use App\Services\CompanyContext;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rules\Unique;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationLabel = 'Suppliers';

    protected static ?string $modelLabel = 'Supplier';

    protected static ?string $pluralModelLabel = 'Suppliers';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'code', 'email', 'phone'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('Supplier Information')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make('Supplier Details')
                        ->icon('heroicon-m-truck')
                        ->schema([
                            Forms\Components\TextInput::make('code')
                                ->required()
                                ->unique(
                                    ignoreRecord: true,
                                    modifyRuleUsing: fn (Unique $rule) => $rule->where('company_id', CompanyContext::getCompanyId())
                                )
                                ->maxLength(50),
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('contact_person')
                                ->maxLength(255),
                            Forms\Components\Textarea::make('address')
                                ->maxLength(65535)
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('city')
                                ->maxLength(100),
                            Forms\Components\TextInput::make('phone')
                                ->tel()
                                ->maxLength(30),
                            Forms\Components\TextInput::make('email')
                                ->email()
                                ->maxLength(100),
                            Forms\Components\TextInput::make('npwp')
                                ->maxLength(30),
                            Forms\Components\TextInput::make('bank_account')
                                ->maxLength(255),
                            Forms\Components\Toggle::make('is_active')
                                ->default(true)
                                ->inline(false),
                        ])
                        ->columns(2),

                    Tabs\Tab::make('Materials')
                        ->icon('heroicon-m-cube')
                        ->visible(fn ($record) => $record !== null)
                        ->schema([
                            Livewire::make(RelationManagers\MaterialsRelationManager::class, fn ($record) => [
                                'ownerRecord' => $record,
                                'pageClass' => Pages\EditSupplier::class,
                            ]),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('code')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('contact_person'),
            Tables\Columns\TextColumn::make('city'),
            Tables\Columns\TextColumn::make('phone'),
            Tables\Columns\TextColumn::make('email')->searchable(),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('is_active')->options(['1' => 'Active', '0' => 'Inactive']),
                SelectFilter::make('company_id')->relationship('company', 'name'),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make()
                        ->before(function (Supplier $record, DeleteAction $action) {
                            $blockers = $record->getDeletionBlockers();
                            if (! empty($blockers)) {
                                Notification::make()
                                    ->title('Cannot Delete Supplier')
                                    ->body("Supplier '{$record->name}' [{$record->code}] cannot be deleted because it is linked to: ".implode(', ', $blockers).'. Please remove or reassign them first.')
                                    ->danger()
                                    ->persistent()
                                    ->send();

                                $action->halt();
                            }
                        }),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (Collection $records, DeleteBulkAction $action) {
                            $blocked = [];
                            foreach ($records as $record) {
                                $blockers = $record->getDeletionBlockers();
                                if (! empty($blockers)) {
                                    $blocked[] = "{$record->code} (".implode(', ', $blockers).')';
                                }
                            }
                            if (! empty($blocked)) {
                                Notification::make()
                                    ->title('Cannot Delete Selected Suppliers')
                                    ->body('Some suppliers cannot be deleted: '.implode('; ', $blocked).'.')
                                    ->danger()
                                    ->persistent()
                                    ->send();

                                $action->halt();
                            }
                        }),
                ]),
            ]);
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-truck';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Master Data';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
