<?php

namespace App\Filament\Resources\RdDesignResource\RelationManagers;

use App\Filament\Actions\StockPreviewAction;
use App\Models\Component;
use App\Models\ConsumptionRate;
use App\Models\Material;
use App\Services\CompanyContext;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;
use OpenSpout\Writer\XLSX\Writer;

class ConsumptionRatesRelationManager extends RelationManager
{
    protected static string $relationship = 'consumptionRates';

    protected static ?string $title = 'Consumption Rates';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('material_id')
                ->relationship('material', 'name')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->formatted_select_label)
                ->searchable(['code', 'name', 'color', 'size'])
                ->preload()
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    $material = $state ? Material::find($state) : null;
                    $set('unit', $material?->uom);
                }),
            Forms\Components\TextInput::make('standard_rate')
                ->numeric()
                ->required()
                ->label(new HtmlString('Actual Consumption <span title="Actual net material requirement per unit (without waste)" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>')),
            Forms\Components\TextInput::make('unit')
                ->label('UOM')
                ->default('pcs')
                ->disabled()
                ->dehydrated(),
            Forms\Components\TextInput::make('wastage_rate')
                ->default(config('costing.wastage_pct', 3))
                ->disabled()
                ->dehydrated()
                ->label(new HtmlString('Yield 3% waste <span title="Production waste tolerance percentage (Fixed global 3%)" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                ->suffix('%'),
            Forms\Components\Select::make('component')
                ->label('Component')
                ->options(function ($state) {
                    $companyId = CompanyContext::getCompanyId();

                    $options = Component::where('company_id', $companyId)
                        ->pluck('name', 'name')
                        ->toArray();

                    if ($state && ! isset($options[$state])) {
                        $options[$state] = $state;
                    }

                    return $options;
                })
                ->searchable()
                ->preload()
                ->createOptionForm([
                    Forms\Components\TextInput::make('name')
                        ->label('Component Name')
                        ->required(),
                ])
                ->createOptionUsing(function (array $data): string {
                    $companyId = CompanyContext::getCompanyId();
                    $comp = Component::firstOrCreate([
                        'company_id' => $companyId,
                        'name' => trim($data['name']),
                    ]);

                    return $comp->name;
                })
                ->createOptionModalHeading('Add New Component')
                ->nullable(),
            Forms\Components\Textarea::make('notes')
                ->maxLength(65535)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->sortable(),
            TextColumn::make('material.name')->sortable()->searchable(),
            TextColumn::make('standard_rate')
                ->label('Actual Consumption')
                ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                ->sortable(),
            TextColumn::make('unit')->label('UOM'),
            TextColumn::make('wastage_rate')
                ->label('Yield 3% waste')
                ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                ->suffix('%'),
            TextColumn::make('component')->sortable()->searchable(),
            TextColumn::make('notes')->limit(50),
        ])
            ->filters([])
            ->headerActions([
                StockPreviewAction::make('form', allowReserve: false),
                CreateAction::make(),
                Action::make('importExcel')
                    ->label('Import Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->form([
                        FileUpload::make('file')
                            ->label('Excel File (.xlsx, .xls)')
                            ->hintAction(
                                Action::make('downloadTemplate')
                                    ->label('Download Template')
                                    ->icon('heroicon-o-arrow-down-tray')
                                    ->color('success')
                                    ->action(function () {
                                        $writer = new Writer;
                                        $tempFilePath = tempnam(sys_get_temp_dir(), 'template').'.xlsx';
                                        $writer->openToFile($tempFilePath);

                                        $writer->addRow(Row::fromValues(['Material Code', 'Component Name', 'Actual Consumption', 'Notes']));
                                        $writer->addRow(Row::fromValues(['FAB-001', 'Body', '1.5', 'Main outer fabric']));
                                        $writer->addRow(Row::fromValues(['ZIP-001', 'Front Pocket', '1', 'Pocket zipper']));
                                        $writer->addRow(Row::fromValues(['ACC-001', 'Handle', '2', 'Handle buckle']));

                                        $writer->close();

                                        return response()->download($tempFilePath, 'consumption_rates_template.xlsx')->deleteFileAfterSend(true);
                                    })
                            )
                            ->disk('local')
                            ->directory('imports')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $file = $data['file'];
                        $filePath = Storage::disk('local')->path($file);

                        try {
                            $reader = new XLSXReader;
                            $reader->open($filePath);

                            $designId = $this->getOwnerRecord()->id;
                            $companyId = CompanyContext::getCompanyId() ?? $this->getOwnerRecord()->company_id;

                            $headers = [];
                            $rowCount = 0;
                            $successCount = 0;
                            $newComponentsCreated = [];
                            $errors = [];

                            foreach ($reader->getSheetIterator() as $sheet) {
                                foreach ($sheet->getRowIterator() as $row) {
                                    $cells = $row->getCells();
                                    $values = array_map(fn ($cell) => $cell->getValue(), $cells);

                                    // Skip completely empty rows
                                    if (empty(array_filter($values, fn ($v) => ! blank($v)))) {
                                        continue;
                                    }

                                    if (empty($headers)) {
                                        $headers = array_map(fn ($v) => strtolower(trim(strval($v))), $values);

                                        continue;
                                    }

                                    $rowCount++;

                                    // Map values to row data
                                    $rowData = array_combine($headers, array_pad($values, count($headers), null));

                                    $materialCode = $rowData['material code'] ?? $rowData['material_code'] ?? $rowData['material'] ?? null;
                                    $componentRaw = $rowData['component name'] ?? $rowData['component_name'] ?? $rowData['component'] ?? $rowData['komponen'] ?? null;
                                    $standardRateRaw = $rowData['actual consumption'] ?? $rowData['actual_consumption'] ?? $rowData['standard rate'] ?? $rowData['standard_rate'] ?? null;
                                    $notes = $rowData['notes'] ?? null;

                                    if (blank($materialCode)) {
                                        $errors[] = "Row {$rowCount}: Material Code is required.";

                                        continue;
                                    }

                                    $standardRate = self::normalizeDecimal($standardRateRaw);
                                    if ($standardRate === null) {
                                        $errors[] = "Row {$rowCount}: Actual Consumption '{$standardRateRaw}' is invalid.";

                                        continue;
                                    }

                                    // Find material
                                    $material = Material::where('code', $materialCode)->first();
                                    if (! $material) {
                                        $errors[] = "Row {$rowCount}: Material '{$materialCode}' not found.";

                                        continue;
                                    }

                                    // Component normalization & auto-registration
                                    $componentName = null;
                                    if (! blank($componentRaw)) {
                                        $trimmedComponent = trim(preg_replace('/\s+/', ' ', strval($componentRaw)));
                                        if ($trimmedComponent !== '') {
                                            $existingComponent = Component::where('company_id', $companyId)
                                                ->whereRaw('LOWER(name) = ?', [strtolower($trimmedComponent)])
                                                ->first();

                                            if ($existingComponent) {
                                                $componentName = $existingComponent->name;
                                            } else {
                                                $newComp = Component::create([
                                                    'company_id' => $companyId,
                                                    'name' => $trimmedComponent,
                                                ]);
                                                $componentName = $newComp->name;
                                                if (! in_array($componentName, $newComponentsCreated, true)) {
                                                    $newComponentsCreated[] = $componentName;
                                                }
                                            }
                                        }
                                    }

                                    // Upsert record by (company_id, design_id, material_id, component)
                                    $consumptionRate = ConsumptionRate::withoutCompanyScope()
                                        ->where('company_id', $companyId)
                                        ->where('design_id', $designId)
                                        ->where('material_id', $material->id)
                                        ->where(function ($q) use ($componentName) {
                                            if ($componentName !== null && $componentName !== '') {
                                                $q->where('component', $componentName);
                                            } else {
                                                $q->whereNull('component')->orWhere('component', '');
                                            }
                                        })
                                        ->first();

                                    if (! $consumptionRate) {
                                        $consumptionRate = new ConsumptionRate;
                                        $consumptionRate->company_id = $companyId;
                                        $consumptionRate->design_id = $designId;
                                        $consumptionRate->material_id = $material->id;
                                    }

                                    $consumptionRate->component = $componentName;
                                    $consumptionRate->standard_rate = $standardRate;
                                    $consumptionRate->wastage_rate = config('costing.wastage_pct', 3);
                                    $consumptionRate->unit = $material->uom ?? $material->uomRef?->name ?? $material->unit;
                                    $consumptionRate->notes = $notes;
                                    $consumptionRate->save();

                                    $successCount++;
                                }
                                break; // Only read the first sheet
                            }

                            $reader->close();
                            Storage::disk('local')->delete($file);

                            $newComponentsCount = count($newComponentsCreated);
                            $compNote = $newComponentsCount > 0
                                ? "\nInfo: {$newComponentsCount} new components automatically registered: ".implode(', ', array_slice($newComponentsCreated, 0, 3)).($newComponentsCount > 3 ? ', etc.' : '.')
                                : '';

                            if (empty($errors)) {
                                Notification::make()
                                    ->title('Excel Import Successful')
                                    ->body("Successfully imported {$successCount} consumption rate records.{$compNote}")
                                    ->success()
                                    ->send();
                            } else {
                                $errorText = implode("\n", array_slice($errors, 0, 5));
                                if (count($errors) > 5) {
                                    $errorText .= "\n...and ".(count($errors) - 5).' more errors.';
                                }

                                Notification::make()
                                    ->title('Import Completed with '.count($errors).' Errors')
                                    ->body("{$successCount} rows imported successfully.{$compNote}\nErrors:\n{$errorText}")
                                    ->warning()
                                    ->persistent()
                                    ->send();
                            }

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Excel Import Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    StockPreviewAction::make('table', allowReserve: false),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
        } elseif (str_contains($state, ',') && ! str_contains($state, '.')) {
            $parts = explode(',', $state);
            if (count($parts) === 2 && strlen($parts[1]) === 3 && (int) $parts[0] > 0) {
                $state = str_replace(',', '', $state);
            } else {
                $state = str_replace(',', '.', $state);
            }
        }

        return is_numeric($state) ? (float) $state : null;
    }
}
