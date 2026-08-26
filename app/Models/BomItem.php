<?php

namespace App\Models;

use App\Services\MerchandisePlanningSyncService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomItem extends Model
{
    protected $fillable = [
        'bom_id',
        'material_id',
        'component',
        'category',
        'quantity_per_unit',
        'unit',
        'wastage_percent',
        'notes',
        'is_from_rnd',
    ];

    protected $casts = [
        'quantity_per_unit' => 'decimal:4',
        'wastage_percent' => 'decimal:2',
        'is_from_rnd' => 'boolean',
    ];

    protected $appends = [
        'filter_category_id',
        'filter_supplier_id',
    ];

    protected static function booted(): void
    {
        static::saving(function (BomItem $bomItem) {
            if (empty($bomItem->category) && $bomItem->material_id) {
                $material = $bomItem->material ?? Material::with('categoryRef')->find($bomItem->material_id);
                $bomItem->category = $material?->categoryRef?->name ?? $material?->category;
            }
        });

        static::saved(function (BomItem $bomItem) {
            MerchandisePlanningSyncService::syncBomItem($bomItem);
        });

        static::deleted(function (BomItem $bomItem) {
            MerchandisePlanningSyncService::deleteBomItem($bomItem);
        });
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class, 'bom_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function getFilterCategoryIdAttribute(): ?int
    {
        return $this->material?->category_id;
    }

    public function getFilterSupplierIdAttribute(): ?int
    {
        return $this->material?->supplier_id;
    }
}
