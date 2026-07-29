<?php

namespace App\Filament\Actions;

use App\Models\ConsumptionRate;
use App\Models\ProductionOrder;
use App\Models\RdDesign;
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
            ->color('info')
            ->modalContent(function ($record = null, $livewire = null) {
                $companyId = CompanyContext::getCompanyId() ?? 1;
                $materials = [];
                $productionQty = 1.0;
                $projectId = null;

                if ($record) {
                    $projectId = $record->project_id ?? null;
                    if (isset($record->planned_qty) || isset($record->qty_sent) || isset($record->qty) || isset($record->quantity)) {
                        $productionQty = (float) ($record->planned_qty ?? $record->qty_sent ?? $record->qty ?? $record->quantity ?? 1.0);
                    }

                    if ($record instanceof ConsumptionRate) {
                        $materials[] = [
                            'material_id' => $record->material_id,
                            'quantity_per_unit' => (float) $record->standard_rate,
                            'unit' => $record->unit ?? 'pcs',
                        ];
                    } elseif ($record instanceof RdDesign && $record->consumptionRates) {
                        $materials = $record->consumptionRates->map(fn ($item) => [
                            'material_id' => $item->material_id,
                            'quantity_per_unit' => (float) $item->standard_rate,
                            'unit' => $item->unit ?? 'pcs',
                        ])->filter(fn ($item) => ! empty($item['material_id']))->values()->all();
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

                if ($livewire) {
                    $owner = method_exists($livewire, 'getOwnerRecord') ? $livewire->getOwnerRecord() : null;
                    if (empty($materials) && $owner && $owner instanceof RdDesign && $owner->consumptionRates) {
                        $materials = $owner->consumptionRates->map(fn ($item) => [
                            'material_id' => $item->material_id,
                            'quantity_per_unit' => (float) $item->standard_rate,
                            'unit' => $item->unit ?? 'pcs',
                        ])->filter(fn ($item) => ! empty($item['material_id']))->values()->all();
                    }

                    $formData = [];
                    if (method_exists($livewire, 'getFormState')) {
                        $formData = $livewire->getFormState();
                    } elseif (property_exists($livewire, 'data') && is_array($livewire->data)) {
                        $formData = $livewire->data;
                    }

                    if (! empty($formData['planned_qty']) || ! empty($formData['qty_sent']) || ! empty($formData['qty']) || ! empty($formData['quantity'])) {
                        $productionQty = (float) ($formData['planned_qty'] ?? $formData['qty_sent'] ?? $formData['qty'] ?? $formData['quantity'] ?? $productionQty);
                    }

                    if (empty($materials)) {
                        $projectId = $formData['project_id'] ?? $projectId;
                        $rawItems = $formData['items'] ?? $formData['materials'] ?? [];
                        if (! empty($rawItems) && is_array($rawItems)) {
                            $materials = collect($rawItems)->map(function ($item) {
                                $qty = $item['quantity_per_unit'] ?? $item['planned_qty'] ?? $item['qty_sent'] ?? $item['qty'] ?? $item['quantity'] ?? 1;

                                return [
                                    'material_id' => $item['material_id'] ?? null,
                                    'quantity_per_unit' => (float) $qty,
                                    'unit' => $item['unit'] ?? 'pcs',
                                ];
                            })->filter(fn ($item) => ! empty($item['material_id']))->values()->all();
                        } elseif (! empty($formData['material_id'])) {
                            $qty = $formData['standard_rate'] ?? $formData['quantity_per_unit'] ?? $formData['qty'] ?? 1;
                            $materials[] = [
                                'material_id' => $formData['material_id'],
                                'quantity_per_unit' => (float) $qty,
                                'unit' => $formData['unit'] ?? 'pcs',
                            ];
                        }
                    }
                }

                return view('filament.actions.stock-preview-modal-wrapper', [
                    'materials' => $materials,
                    'productionQty' => $productionQty,
                    'companyId' => $companyId,
                    'projectId' => $projectId,
                ]);
            });

        return $action;
    }
}
