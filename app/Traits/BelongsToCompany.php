<?php

namespace App\Traits;

use App\Services\CompanyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::creating(function (Model $model) {
            if ($model->company_id === null && auth()->check()) {
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

    public static function withoutCompanyScope(): Builder
    {
        return (new static())->newQuery()->withoutGlobalScope('company');
    }

    public static function allCompanies(): Builder
    {
        return (new static())->newQuery()->withoutGlobalScope('company');
    }
}