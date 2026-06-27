<?php

namespace App\Models;

use App\Services\InventoryService;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubconMaterialIn extends Model
{
    use BelongsToCompany;

    protected $table = 'subcon_material_ins';

    protected $fillable = [
        'company_id',
        'po_subcon_id',
        'subcon_material_out_id',
        'subcon_id',
        'document_number',
        'receive_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'receive_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::updated(function (SubconMaterialIn $in) {
            if ($in->status === 'verified' && $in->getOriginal('status') !== 'verified') {
                InventoryService::receiveFromSubcon($in);
            }
        });

        static::created(function (SubconMaterialIn $in) {
            if ($in->status === 'verified') {
                InventoryService::receiveFromSubcon($in);
            }
        });
    }

    public function poSubcon(): BelongsTo
    {
        return $this->belongsTo(PoSubcon::class, 'po_subcon_id');
    }

    public function subconMaterialOut(): BelongsTo
    {
        return $this->belongsTo(SubconMaterialOut::class, 'subcon_material_out_id');
    }

    public function subcon(): BelongsTo
    {
        return $this->belongsTo(Subcon::class, 'subcon_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SubconMaterialInItem::class, 'subcon_material_in_id');
    }
}
