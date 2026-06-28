<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\InventoryStock;
use App\Models\InventoryMovement;
use App\Models\SubconMaterialOut;
use App\Models\SubconMaterialIn;
use App\Models\Material;
use App\Models\StockTransfer;

class InventoryService
{
    public static function receiveGoods(GoodsReceipt $receipt): void
    {
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
                'notes' => 'Received via Goods Receipt ' . $receipt->gr_number,
            ]);

            self::syncMaterialTotalStock($item->material_id);
        }
    }

    public static function sendToSubcon(SubconMaterialOut $out): void
    {
        // Typically, we send materials out from the main warehouse to subcon
        // Find main warehouse or use the first available warehouse for the company
        $mainWarehouseId = \App\Models\Warehouse::where('company_id', $out->company_id)
            ->where('code', 'WH-MAIN')
            ->first()?->id ?? \App\Models\Warehouse::where('company_id', $out->company_id)->first()?->id;

        if (!$mainWarehouseId) {
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
                    'notes' => 'Sent to Subcon via Document ' . $out->document_number,
                ]);

                self::syncMaterialTotalStock($item->material_id);
            }
        }
    }

    public static function receiveFromSubcon(SubconMaterialIn $in): void
    {
        // Receive raw/processed goods back to main warehouse
        $mainWarehouseId = \App\Models\Warehouse::where('company_id', $in->company_id)
            ->where('code', 'WH-MAIN')
            ->first()?->id ?? \App\Models\Warehouse::where('company_id', $in->company_id)->first()?->id;

        if (!$mainWarehouseId) {
            return;
        }

        foreach ($in->items as $item) {
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
                'notes' => 'Received from Subcon via Document ' . $in->document_number,
            ]);

            self::syncMaterialTotalStock($item->material_id);
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
                    'notes' => 'Transferred out via Stock Transfer ' . $transfer->transfer_number,
                ]);

                self::syncMaterialTotalStock($item->material_id);
            }
        }
    }

    public static function executeTransferReceipt(StockTransfer $transfer): void
    {
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
                'notes' => 'Received via Stock Transfer ' . $transfer->transfer_number,
            ]);

            self::syncMaterialTotalStock($item->material_id);
        }
    }
}
