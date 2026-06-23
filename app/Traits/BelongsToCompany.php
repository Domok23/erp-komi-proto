<?php

namespace App\Traits;

use App\Services\CompanyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 * @method static void creating(callable $callback)
 * @method static void addGlobalScope(string $name, callable $scope)
 * @method \Illuminate\Database\Eloquent\Builder newQuery()
 */
trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::creating(function (Model $model) {
            if ($model->company_id === null && \Illuminate\Support\Facades\Auth::check()) {
                $model->company_id = CompanyContext::getCompanyId();
            }
        });

        static::addGlobalScope('company', function (Builder $builder) {
            $companyId = CompanyContext::getCompanyId();
            if ($companyId !== null) {
                $builder->where($builder->getModel()->getTable() . '.company_id', $companyId);
            }
        });
    }

    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public static function withoutCompanyScope(): Builder
    {
        return (new static())->newQuery()->withoutGlobalScope('company');
    }

    public static function allCompanies(): Builder
    {
        return (new static())->newQuery()->withoutGlobalScope('company');
    }
}