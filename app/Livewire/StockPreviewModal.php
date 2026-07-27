<?php

namespace App\Livewire;

use App\Models\Material;
use App\Services\StockPreviewService;
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

    public array $selected = [];

    public array $selectedTableRecords = [];

    public function mount(array $materials = [], float $productionQty = 1.0, int $companyId = 1, ?int $projectId = null)
    {
        $this->materials = $materials;
        $this->productionQty = max(0.01, $productionQty);
        $this->companyId = $companyId;
        $this->projectId = $projectId;
    }

    public function updatedProductionQty()
    {
        $this->resetPage();
    }

    public function getPreviewData(): \Illuminate\Support\Collection
    {
        return app(StockPreviewService::class)->preview(
            materials: $this->materials,
            productionQty: (float) max(0.01, floatval($this->productionQty)),
            companyId: $this->companyId
        )->keyBy('materialId');
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
                    ->action(function (Collection $records) {
                        $previewData = $this->getPreviewData();
                        $items = $records->map(function ($record) use ($previewData) {
                            $data = $previewData->get($record->id);
                            $qty = $data ? ($data->toBuy > 0 ? $data->toBuy : $data->required) : 0;

                            return ['material_id' => $record->id, 'qty' => $qty];
                        })->filter(fn ($item) => $item['qty'] > 0)->values()->all();

                        if (! empty($items)) {
                            app(StockPreviewService::class)->reserve(reservations: $items, companyId: $this->companyId);
                            Notification::make()
                                ->title('Stock Reserved')
                                ->body(count($items).' material(s) reserved successfully.')
                                ->success()
                                ->send();

                            $this->dispatch('reserved', count($items));
                        }
                    }),
                BulkAction::make('create_po_selected')
                    ->label('Create PO for Selected')
                    ->icon('heroicon-o-shopping-bag')
                    ->color('warning')
                    ->action(function (Collection $records) {
                        $previewData = $this->getPreviewData();
                        $items = $records->map(function ($record) use ($previewData) {
                            $data = $previewData->get($record->id);
                            $qty = $data ? ($data->toBuy > 0 ? $data->toBuy : $data->required) : 0;

                            return ['material_id' => $record->id, 'qty' => $qty];
                        })->filter(fn ($item) => $item['qty'] > 0)->values()->all();

                        if (! empty($items)) {
                            $pos = app(StockPreviewService::class)->createPurchaseOrder(materials: $items, companyId: $this->companyId, projectId: $this->projectId);
                            Notification::make()
                                ->title('Purchase Orders Created')
                                ->body(count($pos).' draft PO(s) generated grouped by supplier.')
                                ->success()
                                ->send();

                            $this->dispatch('po-created', count($pos));
                        }
                    }),
            ]);
    }

    public function render()
    {
        return view('livewire.stock-preview-modal');
    }
}
