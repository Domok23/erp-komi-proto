<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'shipment_number',
        'sales_order_id',
        'shipment_date',
        'status',
        'shipping_method',
        'container_number',
        'bl_number',
        'carrier',
        'port_of_loading',
        'port_of_discharge',
        'etd',
        'eta',
        'total_packages',
        'total_gross_weight_kg',
        'total_volume_m3',
        'shipping_cost_usd',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'shipment_date' => 'date',
        'etd' => 'date',
        'eta' => 'date',
        'total_gross_weight_kg' => 'decimal:2',
        'total_volume_m3' => 'decimal:4',
        'shipping_cost_usd' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::updated(function (Shipment $shipment) {
            if (in_array($shipment->status, ['in_transit', 'delivered']) && !in_array($shipment->getOriginal('status'), ['in_transit', 'delivered'])) {
                InventoryService::processShipment($shipment);
            } elseif ($shipment->status === 'cancelled' && $shipment->getOriginal('status') !== 'cancelled') {
                InventoryService::reverseShipment($shipment);
            }
        });

        static::created(function (Shipment $shipment) {
            if (in_array($shipment->status, ['in_transit', 'delivered'])) {
                InventoryService::processShipment($shipment);
            }
        });

        static::deleted(function (Shipment $shipment) {
            InventoryService::reverseShipment($shipment);
        });
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }
}
