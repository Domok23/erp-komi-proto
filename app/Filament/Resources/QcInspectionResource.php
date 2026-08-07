<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QcInspectionResource\Pages;
use App\Models\JobOrder;
use App\Models\QcInspection;
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
use Illuminate\Support\HtmlString;

class QcInspectionResource extends Resource
{
    protected static ?string $model = QcInspection::class;

    protected static ?string $navigationLabel = 'QC Inspections';

    protected static ?string $modelLabel = 'QC Inspection';

    protected static ?string $pluralModelLabel = 'QC Inspections';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Inspection Details')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('inspection_number')
                        ->disabled()
                        ->dehydrated()
                        ->default(fn () => CodeGenerator::generateQcInspectionNumber())
                        ->required()
                        ->maxLength(50),
                    Forms\Components\Select::make('job_order_id')
                        ->relationship('jobOrder', 'job_order_number')
                        ->getOptionLabelFromRecordUsing(fn ($record) => new HtmlString('<a href="'.JobOrderResource::getUrl('edit', ['record' => $record]).'" class="ref-link">'.$record->job_order_number.'</a>'))
                        ->allowHtml()
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $jobOrder = JobOrder::find($state);
                                if ($jobOrder) {
                                    $set('sample_size', $jobOrder->planned_qty);
                                }
                            } else {
                                $set('sample_size', 0);
                            }
                        }),
                    Forms\Components\DatePicker::make('inspection_date')
                        ->native(false)
                        ->default(now())
                        ->required(),
                    Forms\Components\TextInput::make('sample_size')
                        ->required()
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('passed_qty')
                        ->required()
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('failed_qty')
                        ->required()
                        ->numeric()
                        ->default(0),
                    Forms\Components\Select::make('result')
                        ->options([
                            'pass' => 'Pass',
                            'fail' => 'Fail',
                            'conditional' => 'Conditional',
                        ])
                        ->default('pass')
                        ->required(),
                    Forms\Components\TextInput::make('inspector')
                        ->maxLength(255),
                    Forms\Components\Textarea::make('notes')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('inspection_number')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('jobOrder.job_order_number')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('inspection_date')->date(),
            Tables\Columns\TextColumn::make('sample_size')
                ->numeric(decimalPlaces: 0, decimalSeparator: '.', thousandsSeparator: ','),
            Tables\Columns\TextColumn::make('passed_qty')
                ->numeric(decimalPlaces: 0, decimalSeparator: '.', thousandsSeparator: ','),
            Tables\Columns\TextColumn::make('failed_qty')
                ->numeric(decimalPlaces: 0, decimalSeparator: '.', thousandsSeparator: ','),
            Tables\Columns\BadgeColumn::make('result')
                ->color(fn (string $state): string => match ($state) {
                    'pass' => 'success',
                    'fail' => 'danger',
                    'conditional' => 'warning',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('inspector')->searchable(),
        ])
            ->filters([
                SelectFilter::make('result')->options([
                    'pass' => 'Pass',
                    'fail' => 'Fail',
                    'conditional' => 'Conditional',
                ]),
                SelectFilter::make('job_order_id')->relationship('jobOrder', 'job_order_number'),
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
        return 'heroicon-o-check-circle';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Production';
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
            'index' => Pages\ListQcInspections::route('/'),
            'create' => Pages\CreateQcInspection::route('/create'),
            'edit' => Pages\EditQcInspection::route('/{record}/edit'),
        ];
    }
}
