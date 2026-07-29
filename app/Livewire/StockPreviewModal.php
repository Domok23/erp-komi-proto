<?php

namespace App\Livewire;

use App\Models\Material;
use App\Services\StockPreviewService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class StockPreviewModal extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public array $materials = [];

    public $productionQty = 1.0;

    public int $companyId = 1;

    public ?int $projectId = null;

    public array $sessionReservedQtys = [];

    public array $sessionOrderedQtys = [];

    public array $selected = [];

    public array $selectedTableRecords = [];

    /** Cached preview results — keyed by material_id. Refreshed on every qty change. */
    public array $cachedPreview = [];

    public function mount(array $materials = [], float $productionQty = 1.0, int $companyId = 1, ?int $projectId = null)
    {
        $this->materials = $materials;
        $this->productionQty = max(0, $productionQty);
        $this->companyId = $companyId;
        $this->projectId = $projectId;
        $this->refreshPreview();
    }

    public function refreshPreview(): void
    {
        $preview = app(StockPreviewService::class)->preview(
            materials: $this->materials,
            productionQty: (float) max(0, floatval($this->productionQty)),
            companyId: $this->companyId,
            projectId: $this->projectId,
            additionalReservedQtys: $this->sessionReservedQtys,
            additionalOrderedQtys: $this->sessionOrderedQtys
        );

        // Serialize to plain array so Livewire can diff the public property
        $this->cachedPreview = $preview->keyBy('materialId')->map(fn ($d) => [
            'materialId'  => $d->materialId,
            'required'    => $d->required,
            'currentStock'=> $d->currentStock,
            'toBuy'       => $d->toBuy,
            'status'      => $d->status,
            'unit'        => $d->unit,
            'onOrder'     => $d->onOrder,
        ])->all();
    }

    public function updatedProductionQty(): void
    {
        $this->refreshPreview();
    }

    public function getPreviewData(): \Illuminate\Support\Collection
    {
        // Map back to object-like so column closures can use ->required, ->toBuy, etc.
        return collect($this->cachedPreview)->keyBy(fn ($d) => (int) $d['materialId'])->map(fn ($d) => (object) $d);
    }

    public function table(Table $table): Table
    {
        $materialIds = collect($this->materials)->pluck('material_id')->unique()->filter()->toArray();

        return $table
            ->heading('Live Stock Preview')
            ->description('Perbandingan stok gudang vs kebutuhan material. Centang material lalu gunakan Bulk Action untuk Reserve atau Buat PO.')
            ->query(
                Material::query()
                    ->whereIn('id', ! empty($materialIds) ? $materialIds : [0])
            )
            ->columns([
                TextColumn::make('code')
                    ->label('Material Code')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Material Name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('current_stock')
                    ->label('Current Stock')
                    ->alignEnd()
                    ->state(function ($record) {
                        $item = $this->getPreviewData()->get($record->id);

                        return number_format($item?->currentStock ?? 0, 2).' '.($item?->unit ?? $record->unit);
                    }),
                TextColumn::make('required_qty')
                    ->label('Required')
                    ->alignEnd()
                    ->state(function ($record) {
                        $item = $this->getPreviewData()->get($record->id);

                        return number_format($item?->required ?? 0, 2).' '.($item?->unit ?? $record->unit);
                    }),
                TextColumn::make('to_buy')
                    ->label('To Buy')
                    ->alignEnd()
                    ->weight('bold')
                    ->color(function ($record) {
                        $item = $this->getPreviewData()->get($record->id);

                        return ($item?->toBuy ?? 0) > 0 ? 'warning' : 'gray';
                    })
                    ->state(function ($record) {
                        $item = $this->getPreviewData()->get($record->id);

                        return number_format($item?->toBuy ?? 0, 2).' '.($item?->unit ?? $record->unit);
                    }),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->state(function ($record) {
                        $item = $this->getPreviewData()->get($record->id);

                        return ucfirst($item?->status ?? 'sufficient');
                    })
                    ->color(function ($record) {
                        $item = $this->getPreviewData()->get($record->id);

                        return match ($item?->status) {
                            'sufficient' => 'success',
                            'ordered' => 'info',
                            'partial' => 'warning',
                            'short' => 'danger',
                            default => 'gray',
                        };
                    }),
            ])
            ->bulkActions([
                BulkAction::make('reserve_selected')
                    ->label('Reserve Selected')
                    ->icon('heroicon-o-lock-closed')
                    ->color('info')
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        $previewData = $this->getPreviewData();
                        $items = $records->map(function ($record) use ($previewData) {
                            $data = $previewData->get($record->id);
                            if (! $data) {
                                return null;
                            }
                            $availableToReserve = min((float) $data->currentStock, (float) $data->required);

                            return $availableToReserve > 0
                                ? ['material_id' => $record->id, 'qty' => $availableToReserve]
                                : null;
                        })->filter()->values()->all();

                        if (empty($items)) {
                            Notification::make()
                                ->title('Tidak Ada Stok Gudang')
                                ->body('Stok gudang kosong untuk material terpilih. Silakan buat PO untuk membeli kekurangan material.')
                                ->warning()
                                ->send();

                            return;
                        }

                        try {
                            app(StockPreviewService::class)->reserve(reservations: $items, companyId: $this->companyId, projectId: $this->projectId);
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Gagal Reserve')
                                ->body('Stok di gudang telah berubah atau diambil oleh transaksi lain.')
                                ->danger()
                                ->send();

                            return;
                        }

                        foreach ($items as $item) {
                            $matId = $item['material_id'];
                            $this->sessionReservedQtys[$matId] = ($this->sessionReservedQtys[$matId] ?? 0) + $item['qty'];
                        }

                        $skippedCount = count($records) - count($items);
                        $body = count($items).' material berhasil direservasi.';
                        if ($skippedCount > 0) {
                            $body .= " ({$skippedCount} material dilewati karena stok 0)";
                        }

                        Notification::make()
                            ->title('Stock Reserved')
                            ->body($body)
                            ->success()
                            ->send();

                        $this->dispatch('reserved', count($items));
                    }),
                BulkAction::make('create_po_selected')
                    ->label('Create PO for Selected')
                    ->icon('heroicon-o-shopping-bag')
                    ->color('warning')
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        $previewData = $this->getPreviewData();
                        $items = $records->map(function ($record) use ($previewData) {
                            $data = $previewData->get($record->id);
                            if (! $data) {
                                return null;
                            }

                            return $data->toBuy > 0
                                ? ['material_id' => $record->id, 'qty' => $data->toBuy]
                                : null;
                        })->filter()->values()->all();

                        if (empty($items)) {
                            Notification::make()
                                ->title('Stok Sudah Cukup')
                                ->body('Seluruh material yang dipilih sudah memiliki stok yang mencukupi. PO tidak perlu dibuat.')
                                ->info()
                                ->send();

                            return;
                        }

                        $materialsWithoutSupplier = $records->filter(function ($record) use ($items) {
                            $isItemToBuy = collect($items)->contains('material_id', $record->id);

                            return $isItemToBuy && empty($record->supplier_id);
                        });

                        if ($materialsWithoutSupplier->isNotEmpty()) {
                            $names = $materialsWithoutSupplier->pluck('name')->implode(', ');
                            Notification::make()
                                ->title('Material Belum Memiliki Supplier')
                                ->body("Material berikut belum diset Supplier-nya di master data: {$names}. Harap set Supplier terlebih dahulu.")
                                ->warning()
                                ->send();
                        }

                        $items = collect($items)->reject(function ($item) use ($materialsWithoutSupplier) {
                            return $materialsWithoutSupplier->contains('id', $item['material_id']);
                        })->values()->all();

                        if (empty($items)) {
                            return;
                        }

                        $pos = app(StockPreviewService::class)->createPurchaseOrder(materials: $items, companyId: $this->companyId, projectId: $this->projectId);

                        foreach ($items as $item) {
                            $matId = $item['material_id'];
                            $this->sessionOrderedQtys[$matId] = ($this->sessionOrderedQtys[$matId] ?? 0) + $item['qty'];
                        }

                        $skippedCount = count($records) - count($items);
                        $body = count($pos).' draft PO berhasil dibuat.';
                        if ($skippedCount > 0) {
                            $body .= " ({$skippedCount} material dilewati karena stok sudah cukup / tanpa supplier)";
                        }

                        Notification::make()
                            ->title('Purchase Orders Created')
                            ->body($body)
                            ->success()
                            ->send();

                        $this->dispatch('po-created', count($pos));
                    }),
            ]);
    }

    public function render()
    {
        return view('livewire.stock-preview-modal');
    }
}
