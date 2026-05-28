<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Session;

class CompanyContext
{
    protected const SESSION_KEY = 'selected_company_id';

    public static function setCompany(Company $company): void
    {
        Session::put(self::SESSION_KEY, $company->id);
    }

    public static function getCompany(): ?Company
    {
        $companyId = Session::get(self::SESSION_KEY);

        if (! $companyId) {
            $user = auth()->user();
            if ($user?->company_id) {
                $companyId = $user->company_id;
            }
        }

        if (! $companyId) {
            return null;
        }

        return Company::find($companyId);
    }

    public static function getCompanyId(): ?int
    {
        return Session::get(self::SESSION_KEY) ?? auth()->user()?->company_id;
    }

    public static function hasCompany(): bool
    {
        return self::getCompanyId() !== null;
    }

    public static function switchCompany(int $companyId): void
    {
        $company = Company::find($companyId);
        if (! $company) {
            return;
        }
        self::setCompany($company);
    }

    public static function clearCompany(): void
    {
        Session::forget(self::SESSION_KEY);
    }
}
