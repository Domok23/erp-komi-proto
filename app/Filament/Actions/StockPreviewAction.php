<?php

namespace App\Filament\Actions;

use App\Models\ConsumptionRate;
use App\Models\ProductionOrder;
use App\Services\CompanyContext;
use Filament\Actions\Action;
use Filament\Support\Enums\IconSize;

class StockPreviewAction
{
    public static function make(string $context = 'form'): Action
    {
        $action = Action::make('stock_preview')
            ->label('Preview Stock')
            ->icon('heroicon-o-eye')
            ->iconSize(IconSize::Medium)
            ->modalHeading('Stock Preview')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->modalContent(function ($record, $get) {
                $companyId = CompanyContext::getCompanyId() ?? 1;
                $materials = [];
                $productionQty = 1.0;
                $projectId = null;

                if ($record) {
                    $projectId = $record->project_id ?? null;

                    if ($record instanceof ConsumptionRate) {
                        $materials[] = [
                            'material_id' => $record->material_id,
                            'quantity_per_unit' => (float) $record->standard_rate,
                            'unit' => $record->unit ?? 'pcs',
                        ];
                    } elseif (method_exists($record, 'items') && $record->items && $record->items->isNotEmpty()) {
                        $materials = $record->items->map(function ($item) {
                            $qty = $item->quantity_per_unit ?? $item->planned_qty ?? $item->qty_sent ?? $item->qty ?? $item->quantity ?? 1;

                            return [
                                'material_id' => $item->material_id,
                                'quantity_per_unit' => (float) $qty,
                                'unit' => $item->unit ?? 'pcs',
                            ];
                        })->filter(fn ($item) => ! empty($item['material_id']))->values()->all();
                    } elseif ($record instanceof ProductionOrder) {
                        $items = $record->merchandisingPlanning?->items ?? collect();
                        $materials = $items->map(fn ($item) => [
                            'material_id' => $item->material_id,
                            'quantity_per_unit' => (float) ($item->planned_qty ?? 1),
                            'unit' => $item->unit ?? 'pcs',
                        ])->filter(fn ($item) => ! empty($item['material_id']))->values()->all();
                    }
                }

                if (empty($materials) && is_callable($get)) {
                    $rawItems = $get('items') ?? $get('materials') ?? [];
                    if (! empty($rawItems) && is_array($rawItems)) {
                        $materials = collect($rawItems)->map(function ($item) {
                            $qty = $item['quantity_per_unit'] ?? $item['planned_qty'] ?? $item['qty_sent'] ?? $item['qty'] ?? $item['quantity'] ?? 1;

                            return [
                                'material_id' => $item['material_id'] ?? null,
                                'quantity_per_unit' => (float) $qty,
                                'unit' => $item['unit'] ?? 'pcs',
                            ];
                        })->filter(fn ($item) => ! empty($item['material_id']))->values()->all();
                    } elseif ($get('material_id')) {
                        $qty = $get('standard_rate') ?? $get('quantity_per_unit') ?? $get('qty') ?? 1;
                        $materials[] = [
                            'material_id' => $get('material_id'),
                            'quantity_per_unit' => (float) $qty,
                            'unit' => $get('unit') ?? 'pcs',
                        ];
                    }
                }

                return view('filament.actions.stock-preview-modal-wrapper', [
                    'materials' => $materials,
                    'productionQty' => $productionQty,
                    'companyId' => $companyId,
                    'projectId' => $projectId,
                ]);
            });

        if ($context === 'form') {
            $action->color('info');
        } else {
            $action->iconButton()->tooltip('Preview stock for this material');
        }

        return $action;
    }
}
