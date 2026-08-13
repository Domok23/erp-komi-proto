<?php

namespace App\Filament\Resources\RdDesignResource\RelationManagers;

use App\Filament\Actions\StockPreviewAction;
use App\Models\Component;
use App\Models\ConsumptionRate;
use App\Models\InventoryStock;
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
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;

class ConsumptionRatesRelationManager extends RelationManager
{
    protected static string $relationship = 'consumptionRates';

    protected static ?string $title = 'Consumption Rates';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('material_id')
                ->relationship('material', 'name')
                ->getOptionLabelFromRecordUsing(function ($record) {
                    $companyId = CompanyContext::getCompanyId();
                    $stock = InventoryStock::where('material_id', $record->id)
                        ->where('company_id', $companyId)
                        ->sum('quantity');

                    return "[{$record->code}] {$record->name} (Stock: ".number_format($stock, 2)." {$record->unit})";
                })
                ->searchable()
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
                ->label(new HtmlString('Standard Rate <span title="Jumlah bersih kebutuhan bahan per unit barang (tanpa wastage)" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>')),
            Forms\Components\TextInput::make('unit')
                ->label('UOM')
                ->default('pcs')
                ->disabled()
                ->dehydrated(),
            Forms\Components\TextInput::make('wastage_rate')
                ->default(config('costing.wastage_pct', 3))
                ->disabled()
                ->dehydrated()
                ->label(new HtmlString('Wastage Rate <span title="Persentase toleransi sisa bahan yang terbuang/rusak saat produksi (Fixed global 3%)" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
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
                ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                ->sortable(),
            TextColumn::make('unit')->label('UOM'),
            TextColumn::make('wastage_rate')
                ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
            TextColumn::make('component')->sortable()->searchable(),
            TextColumn::make('notes')->limit(50),
        ])
            ->filters([])
            ->headerActions([
                StockPreviewAction::make('form'),
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
                                        $writer = new \OpenSpout\Writer\XLSX\Writer;
                                        $tempFilePath = tempnam(sys_get_temp_dir(), 'template').'.xlsx';
                                        $writer->openToFile($tempFilePath);

                                        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Material Code', 'Standard Rate', 'Wastage Rate', 'Notes']));
                                        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['FAB-001', '1.5', '10', 'Main outer fabric']));
                                        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['ZIP-001', '1', '0', 'Pocket zipper']));

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
                            $companyId = CompanyContext::getCompanyId();

                            $headers = [];
                            $rowCount = 0;
                            $successCount = 0;
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
                                    $standardRateRaw = $rowData['standard rate'] ?? $rowData['standard_rate'] ?? null;
                                    $wastageRateRaw = $rowData['wastage rate'] ?? $rowData['wastage_rate'] ?? 0;
                                    $notes = $rowData['notes'] ?? null;

                                    if (blank($materialCode)) {
                                        $errors[] = "Row {$rowCount}: Material Code is required.";

                                        continue;
                                    }

                                    $standardRate = self::normalizeDecimal($standardRateRaw);
                                    if ($standardRate === null) {
                                        $errors[] = "Row {$rowCount}: Standard Rate '{$standardRateRaw}' is invalid.";

                                        continue;
                                    }

                                    $wastageRate = self::normalizeDecimal($wastageRateRaw) ?? 0.0;

                                    // Find material
                                    $material = Material::where('code', $materialCode)->first();
                                    if (! $material) {
                                        $errors[] = "Row {$rowCount}: Material '{$materialCode}' not found.";

                                        continue;
                                    }

                                    // Upsert record
                                    $consumptionRate = ConsumptionRate::withoutCompanyScope()
                                        ->where('company_id', $companyId)
                                        ->where('design_id', $designId)
                                        ->where('material_id', $material->id)
                                        ->first();

                                    if (! $consumptionRate) {
                                        $consumptionRate = new ConsumptionRate;
                                        $consumptionRate->company_id = $companyId;
                                        $consumptionRate->design_id = $designId;
                                        $consumptionRate->material_id = $material->id;
                                    }

                                    $consumptionRate->standard_rate = $standardRate;
                                    $consumptionRate->wastage_rate = config('costing.wastage_pct', 3);
                                    $consumptionRate->unit = $material->uom;
                                    $consumptionRate->notes = $notes;
                                    $consumptionRate->save();

                                    $successCount++;
                                }
                                break; // Only read the first sheet
                            }

                            $reader->close();
                            Storage::disk('local')->delete($file);

                            if (empty($errors)) {
                                Notification::make()
                                    ->title('Import Excel Berhasil')
                                    ->body("Berhasil mengimpor {$successCount} data consumption rate.")
                                    ->success()
                                    ->send();
                            } else {
                                $errorText = implode("\n", array_slice($errors, 0, 5));
                                if (count($errors) > 5) {
                                    $errorText .= "\n...dan ".(count($errors) - 5).' error lainnya.';
                                }

                                Notification::make()
                                    ->title('Import Selesai dengan '.count($errors).' Error')
                                    ->body("{$successCount} baris berhasil diimpor.\nError:\n{$errorText}")
                                    ->warning()
                                    ->persistent()
                                    ->send();
                            }

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Import Excel Gagal')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    StockPreviewAction::make('table'),
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
