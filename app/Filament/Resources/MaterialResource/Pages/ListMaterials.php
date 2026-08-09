<?php

namespace App\Filament\Resources\MaterialResource\Pages;

use App\Filament\Resources\MaterialResource;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\MaterialUom;
use App\Models\Supplier;
use App\Services\CompanyContext;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;

class ListMaterials extends ListRecords
{
    protected static string $resource = MaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('downloadTemplate')
                ->label('Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    $writer = new XLSXWriter;
                    $tempFilePath = tempnam(sys_get_temp_dir(), 'template').'.xlsx';
                    $writer->openToFile($tempFilePath);

                    $writer->addRow(Row::fromValues(['Code', 'Name', 'Size', 'Color', 'Category', 'UOM', 'Stock', 'Min Stock', 'Price', 'Is Import', 'Supplier', 'Description']));
                    $writer->addRow(Row::fromValues(['FAB-001', 'Cotton Fabric Red', 'XL', 'Red', 'Fabric', 'kg', '100', '10', '50000', '1', 'SUP-001', 'High quality cotton']));
                    $writer->addRow(Row::fromValues(['ZIP-001', 'YKK Zipper 20cm', '', '', 'Zipper', 'pcs', '500', '50', '2000', '0', 'SUP-002', 'YKK nylon coil zipper']));

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
                        $reader = new XLSXReader;
                        $reader->open($filePath);

                        $headers = [];
                        $rowCount = 0;
                        $successCount = 0;
                        $errors = [];
                        $companyId = CompanyContext::getCompanyId();

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

                                $code = $rowData['code'] ?? null;
                                $name = $rowData['name'] ?? null;
                                $sizeRaw = $rowData['size'] ?? null;
                                $colorRaw = $rowData['color'] ?? null;
                                $categoryRaw = $rowData['category'] ?? null;
                                $uomRaw = $rowData['uom'] ?? $rowData['unit'] ?? 'pcs';
                                $stockRaw = $rowData['stock'] ?? 0.0;
                                $minStockRaw = $rowData['min stock'] ?? $rowData['min_stock'] ?? 0.0;
                                $priceRaw = $rowData['price'] ?? 0.0;
                                $isImportRaw = $rowData['is import'] ?? $rowData['is_import'] ?? 0;
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

                                // Resolve/create category
                                $categoryId = null;
                                if (filled($categoryRaw)) {
                                    $cat = MaterialCategory::withoutGlobalScope('company')
                                        ->where('company_id', $companyId)
                                        ->where('name', trim($categoryRaw))
                                        ->first();
                                    if (! $cat) {
                                        $cat = MaterialCategory::create([
                                            'company_id' => $companyId,
                                            'name' => trim($categoryRaw),
                                        ]);
                                    }
                                    $categoryId = $cat->id;
                                }

                                // Resolve/create UOM
                                $uomId = null;
                                if (filled($uomRaw)) {
                                    $uomModel = MaterialUom::withoutGlobalScope('company')
                                        ->where('company_id', $companyId)
                                        ->where('name', trim($uomRaw))
                                        ->first();
                                    if (! $uomModel) {
                                        $uomModel = MaterialUom::create([
                                            'company_id' => $companyId,
                                            'name' => trim($uomRaw),
                                        ]);
                                    }
                                    $uomId = $uomModel->id;
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
                                    $material = new Material;
                                    $material->code = $code;
                                }

                                $material->name = $name;
                                $material->size = filled($sizeRaw) ? trim($sizeRaw) : null;
                                $material->color = filled($colorRaw) ? trim($colorRaw) : null;
                                $material->category = $categoryRaw;
                                $material->category_id = $categoryId;
                                $material->uom = $uomRaw;
                                $material->uom_id = $uomId;
                                $material->stock = $stock;
                                $material->min_stock = $minStock;
                                $material->price = $price;
                                $material->is_import = (bool) $isImportRaw;
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
                            Notification::make()
                                ->title('Import Excel Berhasil')
                                ->body("Berhasil mengimpor {$successCount} data material.")
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
        ];
    }
}
