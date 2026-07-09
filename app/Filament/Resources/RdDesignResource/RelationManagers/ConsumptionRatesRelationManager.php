<?php

namespace App\Filament\Resources\RdDesignResource\RelationManagers;

use App\Models\InventoryStock;
use App\Models\Material;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;
use OpenSpout\Common\Entity\Row;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;
use App\Models\ConsumptionRate;
use App\Services\CompanyContext;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

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
                    $set('unit', $material?->unit);
                }),
            Forms\Components\TextInput::make('standard_rate')
                ->numeric()
                ->required()
                ->label(new HtmlString('Standard Rate <span title="Jumlah bersih kebutuhan bahan per unit barang (tanpa wastage)" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>')),
            Forms\Components\TextInput::make('unit')
                ->default('pcs')
                ->disabled()
                ->dehydrated(),
            Forms\Components\TextInput::make('wastage_rate')
                ->numeric()
                ->default(0)
                ->required()
                ->label(new HtmlString('Wastage Rate <span title="Persentase toleransi sisa bahan yang terbuang/rusak saat produksi" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                ->suffix('%'),
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
            TextColumn::make('unit'),
            TextColumn::make('wastage_rate')
                ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
            TextColumn::make('notes')->limit(50),
        ])
            ->filters([])
            ->headerActions([
                CreateAction::make(),
                Action::make('downloadTemplate')
                    ->label('Download Template')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function () {
                        $writer = new XLSXWriter();
                        $tempFilePath = tempnam(sys_get_temp_dir(), 'template') . '.xlsx';
                        $writer->openToFile($tempFilePath);
                        
                        $writer->addRow(Row::fromValues(['Material Code', 'Standard Rate', 'Wastage Rate', 'Notes']));
                        $writer->addRow(Row::fromValues(['FAB-001', '1.5', '10', 'Main outer fabric']));
                        $writer->addRow(Row::fromValues(['ZIP-001', '1', '0', 'Pocket zipper']));
                        
                        $writer->close();
                        
                        return response()->download($tempFilePath, 'consumption_rates_template.xlsx')->deleteFileAfterSend(true);
                    }),
                Action::make('importExcel')
                    ->label('Import Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->form([
                        FileUpload::make('file')
                            ->label('Excel File (.xlsx, .xls)')
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
                            $reader = new XLSXReader();
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
                                    if (empty(array_filter($values, fn ($v) => !blank($v)))) {
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
                                        $consumptionRate = new ConsumptionRate();
                                        $consumptionRate->company_id = $companyId;
                                        $consumptionRate->design_id = $designId;
                                        $consumptionRate->material_id = $material->id;
                                    }
                                    
                                    $consumptionRate->standard_rate = $standardRate;
                                    $consumptionRate->wastage_rate = $wastageRate;
                                    $consumptionRate->unit = $material->unit;
                                    $consumptionRate->notes = $notes;
                                    $consumptionRate->save();
                                    
                                    $successCount++;
                                }
                                break; // Only read the first sheet
                            }
                            
                            $reader->close();
                            Storage::disk('local')->delete($file);
                            
                            if (empty($errors)) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Import Excel Berhasil')
                                    ->body("Berhasil mengimpor {$successCount} data consumption rate.")
                                    ->success()
                                    ->send();
                            } else {
                                $errorText = implode("\n", array_slice($errors, 0, 5));
                                if (count($errors) > 5) {
                                    $errorText .= "\n...dan " . (count($errors) - 5) . " error lainnya.";
                                }
                                
                                \Filament\Notifications\Notification::make()
                                    ->title("Import Selesai dengan " . count($errors) . " Error")
                                    ->body("{$successCount} baris berhasil diimpor.\nError:\n{$errorText}")
                                    ->warning()
                                    ->persistent()
                                    ->send();
                            }
                            
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Import Excel Gagal')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                ActionGroup::make([
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
}
