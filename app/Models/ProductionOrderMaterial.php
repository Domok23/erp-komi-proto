<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderMaterial extends Model
{
    use BelongsToCompany;

    protected $table = 'production_order_materials';

    protected $fillable = [
        'company_id',
        'production_order_id',
        'merchandising_planning_item_id',
        'material_id',
        'planned_qty',
        'unit',
        'is_selected',
        'notes',
    ];

    protected $casts = [
        'planned_qty' => 'decimal:3',
        'is_selected' => 'boolean',
    ];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function merchandisingPlanningItem(): BelongsTo
    {
        return $this->belongsTo(MerchandisePlanningItem::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
