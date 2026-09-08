<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'account_code',
        'account_name',
        'account_type',
        'parent_id',
        'balance',
        'is_active',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
    }

    public function debitEntries(): HasMany
    {
        return $this->hasMany(GeneralLedger::class, 'debit_account_id');
    }

    public function creditEntries(): HasMany
    {
        return $this->hasMany(GeneralLedger::class, 'credit_account_id');
    }

    /**
     * @return list<string>
     */
    public function getDeletionBlockers(): array
    {
        $blockers = [];

        if ($count = $this->children()->count()) {
            $blockers[] = "{$count} Sub-Account(s)";
        }
        $glCount = $this->debitEntries()->count() + $this->creditEntries()->count();
        if ($glCount > 0) {
            $blockers[] = "{$glCount} General Ledger Entry/Entries";
        }

        return $blockers;
    }
}
