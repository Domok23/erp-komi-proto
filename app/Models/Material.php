<?php

namespace App\Models;

use App\Services\CompanyContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    protected $fillable = [
        'code',
        'name',
        'size',
        'color',
        'category',
        'category_id',
        'uom',
        'uom_id',
        'stock',
        'min_stock',
        'price',
        'is_import',
        'supplier_id',
        'description',
        'is_active',
    ];

    protected $casts = [
        'stock' => 'decimal:2',
        'min_stock' => 'decimal:2',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_import' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Material $material) {
            $companyId = $material->company_id ?? CompanyContext::getCompanyId() ?? Company::first()?->id;

            // Auto sync category & category_id
            if ($material->category_id && ! $material->category) {
                $material->category = $material->categoryRef?->name;
            } elseif ($material->category && ! $material->category_id) {
                if ($companyId) {
                    $cat = MaterialCategory::withoutGlobalScope('company')
                        ->where('company_id', $companyId)
                        ->where(function ($q) use ($material) {
                            $q->where('name', '=', $material->category)
                                ->orWhere('code', '=', $material->category);
                        })
                        ->first();
                    if (! $cat) {
                        $cat = MaterialCategory::create([
                            'company_id' => $companyId,
                            'name' => ucfirst(str_replace('_', ' ', $material->category)),
                            'code' => $material->category,
                        ]);
                    }
                    $material->category_id = $cat->id;
                }
            }

            // Auto sync uom & uom_id
            if ($material->uom_id && ! $material->uom) {
                $material->uom = $material->uomRef?->name;
            } elseif ($material->uom && ! $material->uom_id) {
                if ($companyId) {
                    $uom = MaterialUom::withoutGlobalScope('company')
                        ->where('company_id', $companyId)
                        ->where('name', '=', $material->uom)
                        ->first();
                    if (! $uom) {
                        $uom = MaterialUom::create([
                            'company_id' => $companyId,
                            'name' => $material->uom,
                        ]);
                    }
                    $material->uom_id = $uom->id;
                }
            }
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function categoryRef(): BelongsTo
    {
        return $this->belongsTo(MaterialCategory::class, 'category_id');
    }

    public function uomRef(): BelongsTo
    {
        return $this->belongsTo(MaterialUom::class, 'uom_id');
    }

    public function inventoryStocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function bomItems(): HasMany
    {
        return $this->hasMany(BomItem::class);
    }

    public function consumptionRates(): HasMany
    {
        return $this->hasMany(ConsumptionRate::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function poSupplierItems(): HasMany
    {
        return $this->hasMany(PoSupplierItem::class);
    }

    public function goodsReceiptItems(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }
}
