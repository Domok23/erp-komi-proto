<?php

namespace App\Models;

use App\Services\CompanyContext;
use App\Services\InventoryService;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use BelongsToCompany;

    protected static function booted(): void
    {
        static::creating(function (InventoryMovement $movement) {
            // Only perform auto-calculations and stock updates if before_qty/after_qty are null (meaning it's a manual creation)
            if ($movement->before_qty === null || $movement->after_qty === null) {
                // If inventory_stock_id is null, find or create it
                if ($movement->inventory_stock_id === null) {
                    // Find main warehouse or first warehouse of the company
                    $companyId = $movement->company_id ?? CompanyContext::getCompanyId();
                    if (! $companyId) {
                        $companyId = 1; // Fallback
                    }

                    $warehouseId = Warehouse::where('company_id', '=', $companyId, 'and')
                        ->where('code', '=', 'WH-MAIN', 'and')
                        ->first()?->id ?? Warehouse::where('company_id', '=', $companyId, 'and')->first()?->id;

                    if (! $warehouseId) {
                        // Create default WH if none exists
                        $warehouse = Warehouse::create([
                            'company_id' => $companyId,
                            'code' => 'WH-MAIN',
                            'name' => 'Main Warehouse',
                            'is_active' => true,
                        ]);
                        $warehouseId = $warehouse->id;
                    }

                    $stock = InventoryStock::firstOrCreate([
                        'company_id' => $companyId,
                        'warehouse_id' => $warehouseId,
                        'material_id' => $movement->material_id,
                    ], [
                        'quantity' => 0,
                        'reserved_qty' => 0,
                        'available_qty' => 0,
                        'unit' => 'pcs',
                        'min_stock' => 0,
                    ]);

                    $movement->inventory_stock_id = $stock->id;
                }

                // Fetch current stock quantity
                $stock = InventoryStock::find($movement->inventory_stock_id, ['*']);
                if ($stock) {
                    $movement->before_qty = $stock->quantity;

                    // Determine whether to add or subtract quantity based on movement type
                    $qty = $movement->quantity;
                    $isSubtraction = in_array($movement->type, ['production_out', 'shipment', 'return_out', 'transfer_out']);

                    if ($isSubtraction) {
                        $movement->after_qty = $movement->before_qty - $qty;
                    } else {
                        // Default to addition
                        $movement->after_qty = $movement->before_qty + $qty;
                    }

                    // Update the stock record
                    $qtyDiff = $movement->after_qty - $movement->before_qty;
                    $stock->update([
                        'quantity' => $movement->after_qty,
                        'available_qty' => $stock->available_qty + $qtyDiff,
                    ]);

                    // Sync total stock in material table
                    InventoryService::syncMaterialTotalStock($movement->material_id);
                }
            }
        });
    }

    protected $fillable = [
        'company_id',
        'inventory_stock_id',
        'material_id',
        'type',
        'reference_type',
        'reference_id',
        'quantity',
        'before_qty',
        'after_qty',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'before_qty' => 'decimal:2',
        'after_qty' => 'decimal:2',
    ];

    public function inventoryStock(): BelongsTo
    {
        return $this->belongsTo(InventoryStock::class, 'inventory_stock_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
