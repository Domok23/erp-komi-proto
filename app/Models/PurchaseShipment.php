<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PurchaseShipment extends Model
{
    use BelongsToCompany;

    protected $table = 'purchase_shipments';

    protected $fillable = [
        'company_id',
        'shipment_number',
        'po_type',
        'po_id',
        'shipment_date',
        'status',
        'shipping_method',
        'carrier',
        'tracking_number',
        'container_number',
        'bl_number',
        'etd',
        'eta',
        'actual_arrival',
        'total_packages',
        'total_gross_weight_kg',
        'total_volume_m3',
        'shipping_cost',
        'notes',
    ];

    protected $casts = [
        'shipment_date' => 'date',
        'etd' => 'date',
        'eta' => 'date',
        'actual_arrival' => 'date',
        'total_gross_weight_kg' => 'decimal:2',
        'total_volume_m3' => 'decimal:4',
        'shipping_cost' => 'decimal:2',
    ];

    public function po(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'po_type', 'po_id');
    }
}
