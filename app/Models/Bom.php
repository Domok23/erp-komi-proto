<?php

namespace App\Models;

use App\Services\CodeGenerator;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bom extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'bom_number',
        'design_id',
        'version',
        'name',
        'status',
        'notes',
    ];

    protected static function booted(): void
    {
        static::saving(function (Bom $bom) {
            if (empty($bom->version)) {
                $bom->version = '1.0';
            }

            if (empty($bom->bom_number) || $bom->isDirty('design_id') || $bom->isDirty('version')) {
                if ($bom->design_id && $bom->version) {
                    $bom->bom_number = CodeGenerator::generateBOMNumber((int) $bom->design_id, $bom->version);
                }
            }
        });
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(RdDesign::class, 'design_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BomItem::class, 'bom_id');
    }
}
