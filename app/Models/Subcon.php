<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subcon extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'service_type',
        'contact_person',
        'address',
        'phone',
        'email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function poSubcons(): HasMany
    {
        return $this->hasMany(PoSubcon::class);
    }

    public function subconMaterialIns(): HasMany
    {
        return $this->hasMany(SubconMaterialIn::class);
    }

    public function subconMaterialOuts(): HasMany
    {
        return $this->hasMany(SubconMaterialOut::class);
    }

    /**
     * @return list<string>
     */
    public function getDeletionBlockers(): array
    {
        $blockers = [];

        if ($count = $this->poSubcons()->count()) {
            $blockers[] = "{$count} PO Subcon(s)";
        }
        if ($count = $this->subconMaterialIns()->count()) {
            $blockers[] = "{$count} Subcon Material In record(s)";
        }
        if ($count = $this->subconMaterialOuts()->count()) {
            $blockers[] = "{$count} Subcon Material Out record(s)";
        }

        return $blockers;
    }
}
