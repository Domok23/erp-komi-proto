<?php

namespace App\Filament\Resources\MaterialResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

use App\Models\Material;
use App\Models\Supplier;
use App\Filament\Resources\MaterialResource;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;
use OpenSpout\Common\Entity\Row;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;

class ListMaterials extends ListRecords
{
    protected static string $resource = 'App\Filament\Resources\MaterialResource';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('downloadTemplate')
                ->label('Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    $writer = new XLSXWriter();
                    $tempFilePath = tempnam(sys_get_temp_dir(), 'template') . '.xlsx';
                    $writer->openToFile($tempFilePath);
                    
                    $writer->addRow(Row::fromValues(['Code', 'Name', 'Category', 'Unit', 'Stock', 'Min Stock', 'Price', 'Supplier', 'Description']));
                    $writer->addRow(Row::fromValues(['FAB-001', 'Cotton Fabric Red', 'Fabric', 'kg', '100', '10', '5.50', 'SUP-001', 'High quality cotton fabric']));
                    $writer->addRow(Row::fromValues(['ZIP-001', 'YKK Zipper 20cm', 'Zipper', 'pcs', '500', '50', '0.80', 'SUP-002', 'YKK nylon coil zipper']));
                    
                    $writer->close();
                    
                    return response()->download($tempFilePath, 'materials_template.xlsx')->deleteFileAfterSend(true);
                }),
            Actions\Action::make('importExcel')
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
                                
                                $code = $rowData['code'] ?? null;
                                $name = $rowData['name'] ?? null;
                                $categoryRaw = $rowData['category'] ?? null;
                                $unit = $rowData['unit'] ?? 'pcs';
                                $stockRaw = $rowData['stock'] ?? 0.0;
                                $minStockRaw = $rowData['min stock'] ?? $rowData['min_stock'] ?? 0.0;
                                $priceRaw = $rowData['price'] ?? 0.0;
                                $supplierRaw = $rowData['supplier'] ?? null;
                                $description = $rowData['description'] ?? null;
                                
                                if (blank($code)) {
                                    $errors[] = "Row {$rowCount}: Code is required.";
                                    continue;
                                }
                                
                                if (blank($name)) {
                                    $errors[] = "Row {$rowCount}: Name is required.";
                                    continue;
                                }
                                
                                $category = MaterialResource::normalizeCategory($categoryRaw);
                                if ($category === null) {
                                    $errors[] = "Row {$rowCount}: Category '{$categoryRaw}' is invalid.";
                                    continue;
                                }
                                
                                $stock = MaterialResource::normalizeDecimal($stockRaw) ?? 0.0;
                                $minStock = MaterialResource::normalizeDecimal($minStockRaw) ?? 0.0;
                                $price = MaterialResource::normalizeDecimal($priceRaw) ?? 0.0;
                                
                                // Find Supplier
                                $supplierId = null;
                                if (filled($supplierRaw)) {
                                    $supplier = Supplier::where('name', $supplierRaw)
                                        ->orWhere('code', $supplierRaw)
                                        ->first();
                                    if ($supplier) {
                                        $supplierId = $supplier->id;
                                    }
                                }
                                
                                // Upsert record
                                $material = Material::where('code', $code)->first();
                                if (! $material) {
                                    $material = new Material();
                                    $material->code = $code;
                                }
                                
                                $material->name = $name;
                                $material->category = $category;
                                $material->unit = $unit;
                                $material->stock = $stock;
                                $material->min_stock = $minStock;
                                $material->price = $price;
                                $material->supplier_id = $supplierId;
                                $material->description = $description;
                                $material->is_active = true;
                                $material->save();
                                
                                $successCount++;
                            }
                            break; // Only read the first sheet
                        }
                        
                        $reader->close();
                        Storage::disk('local')->delete($file);
                        
                        if (empty($errors)) {
                            \Filament\Notifications\Notification::make()
                                ->title('Import Excel Berhasil')
                                ->body("Berhasil mengimpor {$successCount} data material.")
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
        ];
    }
}
