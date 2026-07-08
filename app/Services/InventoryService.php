<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptRetur;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\ProductionOrder;
use App\Models\Shipment;
use App\Models\StockTransfer;
use App\Models\SubconMaterialIn;
use App\Models\SubconMaterialOut;
use App\Models\Warehouse;

class InventoryService
{
    public static function receiveGoods(GoodsReceipt $receipt): void
    {
        $hasMovements = InventoryMovement::where('reference_type', GoodsReceipt::class)
            ->where('reference_id', $receipt->id)
            ->exists();

        if ($hasMovements) {
            return;
        }

        foreach ($receipt->items as $item) {
            $stock = InventoryStock::firstOrCreate([
                'company_id' => $receipt->company_id,
                'warehouse_id' => $receipt->warehouse_id,
                'material_id' => $item->material_id,
            ], [
                'quantity' => 0,
                'reserved_qty' => 0,
                'available_qty' => 0,
                'unit' => $item->unit ?? 'pcs',
                'min_stock' => 0,
            ]);

            $beforeQty = $stock->quantity;
            $afterQty = $beforeQty + $item->qty_received;

            $stock->update([
                'quantity' => $afterQty,
                'available_qty' => $stock->available_qty + $item->qty_received,
            ]);

            InventoryMovement::create([
                'company_id' => $receipt->company_id,
                'inventory_stock_id' => $stock->id,
                'material_id' => $item->material_id,
                'type' => 'purchase',
                'reference_type' => GoodsReceipt::class,
                'reference_id' => $receipt->id,
                'quantity' => $item->qty_received,
                'before_qty' => $beforeQty,
                'after_qty' => $afterQty,
                'notes' => 'Received via Goods Receipt '.$receipt->gr_number,
            ]);

            self::syncMaterialTotalStock($item->material_id);
        }
    }

    public static function sendToSubcon(SubconMaterialOut $out): void
    {
        $hasMovements = InventoryMovement::where('reference_type', SubconMaterialOut::class)
            ->where('reference_id', $out->id)
            ->exists();

        if ($hasMovements) {
            return;
        }

        // Typically, we send materials out from the main warehouse to subcon
        // Find main warehouse or use the first available warehouse for the company
        $mainWarehouseId = Warehouse::where('company_id', $out->company_id)
            ->where('code', 'WH-MAIN')
            ->first()?->id ?? Warehouse::where('company_id', $out->company_id)->first()?->id;

        if (! $mainWarehouseId) {
            return;
        }

        foreach ($out->items as $item) {
            $stock = InventoryStock::where('company_id', $out->company_id)
                ->where('warehouse_id', $mainWarehouseId)
                ->where('material_id', $item->material_id)
                ->first();

            if ($stock) {
                $beforeQty = $stock->quantity;
                $afterQty = $beforeQty - $item->qty_sent;

                $stock->update([
                    'quantity' => $afterQty,
                    'available_qty' => $stock->available_qty - $item->qty_sent,
                ]);

                InventoryMovement::create([
                    'company_id' => $out->company_id,
                    'inventory_stock_id' => $stock->id,
                    'material_id' => $item->material_id,
                    'type' => 'production_out',
                    'reference_type' => SubconMaterialOut::class,
                    'reference_id' => $out->id,
                    'quantity' => $item->qty_sent,
                    'before_qty' => $beforeQty,
                    'after_qty' => $afterQty,
                    'notes' => 'Sent to Subcon via Document '.$out->document_number,
                ]);

                self::syncMaterialTotalStock($item->material_id);
            }
        }
    }

    public static function receiveFromSubcon(SubconMaterialIn $in): void
    {
        $hasMovements = InventoryMovement::where('reference_type', SubconMaterialIn::class)
            ->where('reference_id', $in->id)
            ->exists();

        if ($hasMovements) {
            return;
        }

        // Receive raw/processed goods back to main warehouse
        $mainWarehouseId = Warehouse::where('company_id', $in->company_id)
            ->where('code', 'WH-MAIN')
            ->first()?->id ?? Warehouse::where('company_id', $in->company_id)->first()?->id;

        if (! $mainWarehouseId) {
            return;
        }

        foreach ($in->items as $item) {
            $itemType = $item->item_type ?? 'processed';

            // Only update stock if we have a material ID and actually received quantity
            if ($item->material_id && $item->qty_received > 0) {
                $stock = InventoryStock::firstOrCreate([
                    'company_id' => $in->company_id,
                    'warehouse_id' => $mainWarehouseId,
                    'material_id' => $item->material_id,
                ], [
                    'quantity' => 0,
                    'reserved_qty' => 0,
                    'available_qty' => 0,
                    'unit' => $item->unit ?? 'pcs',
                    'min_stock' => 0,
                ]);

                $beforeQty = $stock->quantity;
                $afterQty = $beforeQty + $item->qty_received;

                $stock->update([
                    'quantity' => $afterQty,
                    'available_qty' => $stock->available_qty + $item->qty_received,
                ]);

                $note = $itemType === 'raw_return'
                    ? "Leftover raw material returned from Subcon via Document {$in->document_number}. Rejected: {$item->qty_rejected}"
                    : "Processed goods received from Subcon via Document {$in->document_number}. Rejected: {$item->qty_rejected}";

                InventoryMovement::create([
                    'company_id' => $in->company_id,
                    'inventory_stock_id' => $stock->id,
                    'material_id' => $item->material_id,
                    'type' => 'production_in',
                    'reference_type' => SubconMaterialIn::class,
                    'reference_id' => $in->id,
                    'quantity' => $item->qty_received,
                    'before_qty' => $beforeQty,
                    'after_qty' => $afterQty,
                    'notes' => $note,
                ]);

                self::syncMaterialTotalStock($item->material_id);
            } elseif ($item->material_id && $item->qty_rejected > 0) {
                // If only rejected items are received (qty_received is 0), we don't increase stock,
                // but we record an inventory movement with 0 quantity change just for logging.
                $stock = InventoryStock::where('company_id', $in->company_id)
                    ->where('warehouse_id', $mainWarehouseId)
                    ->where('material_id', $item->material_id)
                    ->first();

                if ($stock) {
                    $note = $itemType === 'raw_return'
                        ? "Rejected raw material from Subcon via Document {$in->document_number} (not added to stock)"
                        : "Rejected processed goods from Subcon via Document {$in->document_number} (not added to stock)";

                    InventoryMovement::create([
                        'company_id' => $in->company_id,
                        'inventory_stock_id' => $stock->id,
                        'material_id' => $item->material_id,
                        'type' => 'production_in',
                        'reference_type' => SubconMaterialIn::class,
                        'reference_id' => $in->id,
                        'quantity' => 0,
                        'before_qty' => $stock->quantity,
                        'after_qty' => $stock->quantity,
                        'notes' => $note.'. Qty: '.$item->qty_rejected,
                    ]);
                }
            }
        }
    }

    public static function syncMaterialTotalStock(int $materialId): void
    {
        $material = Material::find($materialId);
        if ($material) {
            $totalStock = InventoryStock::where('material_id', $materialId)->sum('quantity');
            $material->update([
                'stock' => $totalStock,
            ]);
        }
    }

    public static function executeTransferShipment(StockTransfer $transfer): void
    {
        $hasMovements = InventoryMovement::where('reference_type', StockTransfer::class)
            ->where('reference_id', $transfer->id)
            ->where('type', 'transfer_out')
            ->exists();

        if ($hasMovements) {
            return;
        }

        foreach ($transfer->items as $item) {
            $stock = InventoryStock::where('company_id', $transfer->from_company_id)
                ->where('warehouse_id', $transfer->from_warehouse_id)
                ->where('material_id', $item->material_id)
                ->first();

            if ($stock) {
                $beforeQty = $stock->quantity;
                $afterQty = $beforeQty - $item->qty_transferred;

                $stock->update([
                    'quantity' => $afterQty,
                    'available_qty' => $stock->available_qty - $item->qty_transferred,
                ]);

                InventoryMovement::create([
                    'company_id' => $transfer->from_company_id,
                    'inventory_stock_id' => $stock->id,
                    'material_id' => $item->material_id,
                    'type' => 'transfer_out',
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->id,
                    'quantity' => $item->qty_transferred,
                    'before_qty' => $beforeQty,
                    'after_qty' => $afterQty,
                    'notes' => 'Transferred out via Stock Transfer '.$transfer->transfer_number,
                ]);

                self::syncMaterialTotalStock($item->material_id);
            }
        }
    }

    public static function executeTransferReceipt(StockTransfer $transfer): void
    {
        $hasMovements = InventoryMovement::where('reference_type', StockTransfer::class)
            ->where('reference_id', $transfer->id)
            ->where('type', 'transfer_in')
            ->exists();

        if ($hasMovements) {
            return;
        }

        foreach ($transfer->items as $item) {
            $stock = InventoryStock::firstOrCreate([
                'company_id' => $transfer->to_company_id,
                'warehouse_id' => $transfer->to_warehouse_id,
                'material_id' => $item->material_id,
            ], [
                'quantity' => 0,
                'reserved_qty' => 0,
                'available_qty' => 0,
                'unit' => $item->unit ?? 'pcs',
                'min_stock' => 0,
            ]);

            $beforeQty = $stock->quantity;
            $afterQty = $beforeQty + $item->qty_transferred;

            $stock->update([
                'quantity' => $afterQty,
                'available_qty' => $stock->available_qty + $item->qty_transferred,
            ]);

            InventoryMovement::create([
                'company_id' => $transfer->to_company_id,
                'inventory_stock_id' => $stock->id,
                'material_id' => $item->material_id,
                'type' => 'transfer_in',
                'reference_type' => StockTransfer::class,
                'reference_id' => $transfer->id,
                'quantity' => $item->qty_transferred,
                'before_qty' => $beforeQty,
                'after_qty' => $afterQty,
                'notes' => 'Received via Stock Transfer '.$transfer->transfer_number,
            ]);

            self::syncMaterialTotalStock($item->material_id);
        }
    }

    public static function processShipment(Shipment $shipment): void
    {
        $hasMovements = InventoryMovement::where('reference_type', Shipment::class)
            ->where('reference_id', $shipment->id)
            ->exists();

        if ($hasMovements) {
            return;
        }

        $so = $shipment->salesOrder;
        if (! $so) {
            return;
        }

        $design = null;
        if ($so->project) {
            $design = $so->project->design;
        } elseif ($so->costing) {
            $design = $so->costing->design;
        }

        if (! $design) {
            return;
        }

        $material = Material::firstOrCreate([
            'code' => $design->code,
        ], [
            'name' => $design->name,
            'category' => 'finished',
            'unit' => 'pcs',
            'stock' => 0,
            'min_stock' => 0,
            'price' => $design->estimated_selling_price ?? 0,
        ]);

        $companyId = $shipment->company_id;
        $warehouseId = Warehouse::where('company_id', $companyId)
            ->where('code', 'WH-MAIN')
            ->first()?->id ?? Warehouse::where('company_id', $companyId)->first()?->id;

        if (! $warehouseId) {
            return;
        }

        $stock = InventoryStock::firstOrCreate([
            'company_id' => $companyId,
            'warehouse_id' => $warehouseId,
            'material_id' => $material->id,
        ], [
            'quantity' => 0,
            'reserved_qty' => 0,
            'available_qty' => 0,
            'unit' => $material->unit ?? 'pcs',
            'min_stock' => 0,
        ]);

        $beforeQty = $stock->quantity;
        $afterQty = $beforeQty - $so->quantity;

        $stock->update([
            'quantity' => $afterQty,
            'available_qty' => $stock->available_qty - $so->quantity,
        ]);

        InventoryMovement::create([
            'company_id' => $companyId,
            'inventory_stock_id' => $stock->id,
            'material_id' => $material->id,
            'type' => 'shipment',
            'reference_type' => Shipment::class,
            'reference_id' => $shipment->id,
            'quantity' => $so->quantity,
            'before_qty' => $beforeQty,
            'after_qty' => $afterQty,
            'notes' => 'Shipped via Shipment '.$shipment->shipment_number,
        ]);

        self::syncMaterialTotalStock($material->id);
    }

    public static function reverseShipment(Shipment $shipment): void
    {
        $movements = InventoryMovement::where('reference_type', Shipment::class)
            ->where('reference_id', $shipment->id)
            ->get();

        foreach ($movements as $movement) {
            $stock = $movement->inventoryStock;
            if ($stock) {
                $newQty = $stock->quantity + $movement->quantity;
                $stock->update([
                    'quantity' => $newQty,
                    'available_qty' => $stock->available_qty + $movement->quantity,
                ]);
            }
            $movement->delete();
            if ($stock) {
                self::syncMaterialTotalStock($stock->material_id);
            }
        }
    }

    public static function processRetur(GoodsReceiptRetur $retur): void
    {
        $hasMovements = InventoryMovement::where('reference_type', GoodsReceiptRetur::class)
            ->where('reference_id', $retur->id)
            ->exists();

        if ($hasMovements) {
            return;
        }

        $goodsReceipt = $retur->goodsReceipt;
        if (! $goodsReceipt) {
            return;
        }

        $companyId = $goodsReceipt->company_id;
        $warehouseId = $goodsReceipt->warehouse_id;

        foreach ($retur->items as $item) {
            $stock = InventoryStock::where('company_id', $companyId)
                ->where('warehouse_id', $warehouseId)
                ->where('material_id', $item->material_id)
                ->first();

            if ($stock) {
                $beforeQty = $stock->quantity;
                $afterQty = $beforeQty - $item->qty_returned;

                $stock->update([
                    'quantity' => $afterQty,
                    'available_qty' => $stock->available_qty - $item->qty_returned,
                ]);

                InventoryMovement::create([
                    'company_id' => $companyId,
                    'inventory_stock_id' => $stock->id,
                    'material_id' => $item->material_id,
                    'type' => 'return_out',
                    'reference_type' => GoodsReceiptRetur::class,
                    'reference_id' => $retur->id,
                    'quantity' => $item->qty_returned,
                    'before_qty' => $beforeQty,
                    'after_qty' => $afterQty,
                    'notes' => 'Returned to supplier via Retur '.$retur->retur_number,
                ]);

                self::syncMaterialTotalStock($item->material_id);
            }
        }
    }

    public static function reverseRetur(GoodsReceiptRetur $retur): void
    {
        $movements = InventoryMovement::where('reference_type', GoodsReceiptRetur::class)
            ->where('reference_id', $retur->id)
            ->get();

        foreach ($movements as $movement) {
            $stock = $movement->inventoryStock;
            if ($stock) {
                $newQty = $stock->quantity + $movement->quantity;
                $stock->update([
                    'quantity' => $newQty,
                    'available_qty' => $stock->available_qty + $movement->quantity,
                ]);
            }
            $movement->delete();
            if ($stock) {
                self::syncMaterialTotalStock($stock->material_id);
            }
        }
    }

    public static function processMaterialUsage(MaterialUsage $usage): void
    {
        $hasMovements = InventoryMovement::where('reference_type', MaterialUsage::class)
            ->where('reference_id', $usage->id)
            ->exists();

        if ($hasMovements) {
            return;
        }

        $companyId = $usage->company_id;
        $warehouseId = Warehouse::where('company_id', $companyId)
            ->where('code', 'WH-MAIN')
            ->first()?->id ?? Warehouse::where('company_id', $companyId)->first()?->id;

        if (! $warehouseId) {
            return;
        }

        $stock = InventoryStock::where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->where('material_id', $usage->material_id)
            ->first();

        if ($stock) {
            $beforeQty = $stock->quantity;
            $afterQty = $beforeQty - $usage->actual_qty;

            $stock->update([
                'quantity' => $afterQty,
                'available_qty' => $stock->available_qty - $usage->actual_qty,
            ]);

            $jobOrderNo = $usage->jobOrder?->job_order_number ?? '';

            InventoryMovement::create([
                'company_id' => $companyId,
                'inventory_stock_id' => $stock->id,
                'material_id' => $usage->material_id,
                'type' => 'production_out',
                'reference_type' => MaterialUsage::class,
                'reference_id' => $usage->id,
                'quantity' => $usage->actual_qty,
                'before_qty' => $beforeQty,
                'after_qty' => $afterQty,
                'notes' => 'Consumed for production in Job Order '.$jobOrderNo,
            ]);

            self::syncMaterialTotalStock($usage->material_id);
        }
    }

    public static function reverseMaterialUsage(MaterialUsage $usage): void
    {
        $movements = InventoryMovement::where('reference_type', MaterialUsage::class)
            ->where('reference_id', $usage->id)
            ->get();

        foreach ($movements as $movement) {
            $stock = $movement->inventoryStock;
            if ($stock) {
                $newQty = $stock->quantity + $movement->quantity;
                $stock->update([
                    'quantity' => $newQty,
                    'available_qty' => $stock->available_qty + $movement->quantity,
                ]);
            }
            $movement->delete();
            if ($stock) {
                self::syncMaterialTotalStock($stock->material_id);
            }
        }
    }

    public static function processProductionOrderCompletion(ProductionOrder $po): void
    {
        $hasMovements = InventoryMovement::where('reference_type', ProductionOrder::class)
            ->where('reference_id', $po->id)
            ->exists();

        if ($hasMovements) {
            return;
        }

        $project = $po->project;
        if (! $project) {
            return;
        }

        $design = $project->design;
        if (! $design) {
            return;
        }

        $material = Material::firstOrCreate([
            'code' => $design->code,
        ], [
            'name' => $design->name,
            'category' => 'finished',
            'unit' => 'pcs',
            'stock' => 0,
            'min_stock' => 0,
            'price' => $design->estimated_selling_price ?? 0,
        ]);

        $companyId = $po->company_id;
        $warehouseId = Warehouse::where('company_id', $companyId)
            ->where('code', 'WH-MAIN')
            ->first()?->id ?? Warehouse::where('company_id', $companyId)->first()?->id;

        if (! $warehouseId) {
            return;
        }

        $stock = InventoryStock::firstOrCreate([
            'company_id' => $companyId,
            'warehouse_id' => $warehouseId,
            'material_id' => $material->id,
        ], [
            'quantity' => 0,
            'reserved_qty' => 0,
            'available_qty' => 0,
            'unit' => $material->unit ?? 'pcs',
            'min_stock' => 0,
        ]);

        $beforeQty = $stock->quantity;
        $qtyToReceive = $po->completed_qty > 0 ? $po->completed_qty : $po->planned_qty;
        $afterQty = $beforeQty + $qtyToReceive;

        $stock->update([
            'quantity' => $afterQty,
            'available_qty' => $stock->available_qty + $qtyToReceive,
        ]);

        InventoryMovement::create([
            'company_id' => $companyId,
            'inventory_stock_id' => $stock->id,
            'material_id' => $material->id,
            'type' => 'production_in',
            'reference_type' => ProductionOrder::class,
            'reference_id' => $po->id,
            'quantity' => $qtyToReceive,
            'before_qty' => $beforeQty,
            'after_qty' => $afterQty,
            'notes' => 'Completed production from Production Order '.$po->production_number,
        ]);

        self::syncMaterialTotalStock($material->id);
    }

    public static function reverseProductionOrderCompletion(ProductionOrder $po): void
    {
        $movements = InventoryMovement::where('reference_type', ProductionOrder::class)
            ->where('reference_id', $po->id)
            ->get();

        foreach ($movements as $movement) {
            $stock = $movement->inventoryStock;
            if ($stock) {
                $newQty = $stock->quantity - $movement->quantity;
                $stock->update([
                    'quantity' => $newQty,
                    'available_qty' => $stock->available_qty - $movement->quantity,
                ]);
            }
            $movement->delete();
            if ($stock) {
                self::syncMaterialTotalStock($stock->material_id);
            }
        }
    }
}
