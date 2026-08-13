<?php

namespace App\Models;

use App\Services\InventoryService;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubconMaterialOut extends Model
{
    use BelongsToCompany;

    protected $table = 'subcon_material_outs';

    protected $fillable = [
        'company_id',
        'po_subcon_id',
        'subcon_id',
        'document_number',
        'departure_date',
        'status',
        'delivery_method',
        'courier_name',
        'delivery_cost',
        'estimated_arrival',
        'notes',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'estimated_arrival' => 'date',
        'delivery_cost' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::updated(function (SubconMaterialOut $out) {
            if ($out->status === 'sent' && $out->getOriginal('status') !== 'sent') {
                InventoryService::sendToSubcon($out);
            }
        });

        static::created(function (SubconMaterialOut $out) {
            if ($out->status === 'sent') {
                InventoryService::sendToSubcon($out);
            }
        });
    }

    public function poSubcon(): BelongsTo
    {
        return $this->belongsTo(PoSubcon::class, 'po_subcon_id');
    }

    public function subcon(): BelongsTo
    {
        return $this->belongsTo(Subcon::class, 'subcon_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SubconMaterialOutItem::class, 'subcon_material_out_id');
    }
}
