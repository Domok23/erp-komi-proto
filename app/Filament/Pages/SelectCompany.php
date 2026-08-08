<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Services\CompanyContext;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class SelectCompany extends Page
{
    protected string $view = 'filament.pages.select-company';

    protected static string $layout = 'filament-panels::components.layout.simple';

    protected static ?string $title = 'Select Company';

    protected static ?string $navigationLabel = 'Select Company';

    protected static ?string $slug = 'select-company';

    protected static bool $shouldRegisterNavigation = false;

    public function mount(): void
    {
        if (request()->query('switch') === '1') {
            CompanyContext::clearCompany();
        } elseif (CompanyContext::hasCompany()) {
            $this->redirect(Filament::getUrl(), navigate: true);
        }
    }

    public function getSubheading(): ?string
    {
        return 'Select the company you want to access';
    }

    public function hasLogo(): bool
    {
        return true;
    }

    protected function getViewData(): array
    {
        return [
            'companies' => Company::query()
                ->where('is_active', true)
                ->orderBy('type')
                ->orderBy('name')
                ->get(),
        ];
    }

    public function selectCompany(int $companyId): void
    {
        $company = Company::query()->find($companyId);
        if (! $company) {
            return;
        }

        CompanyContext::setCompany($company);

        $this->redirect(Filament::getUrl(), navigate: true);
    }
}
