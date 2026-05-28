<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Services\CompanyContext;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Pages\Page;

class SelectCompany extends Page
{
    protected string $view = 'filament.pages.select-company';

    protected static ?string $title = 'Pilih Perusahaan';

    protected static ?string $navigationLabel = 'Pilih Perusahaan';

    protected static ?string $slug = 'select-company';

    protected static bool $shouldRegisterNavigation = false;

    public ?string $selectedCompany = null;

    public function mount(): void
    {
        if (CompanyContext::hasCompany()) {
            $this->redirect(Filament::getUrl());
        }
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

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Select::make('selectedCompany')
                ->label('Pilih Perusahaan')
                ->options(
                    Company::query()
                        ->where('is_active', true)
                        ->orderBy('type')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                )
                ->required()
                ->placeholder('-- Pilih Perusahaan --'),
        ]);
    }

    protected function getActions(): array
    {
        return [];
    }

    public function submit(): void
    {
        $this->validate([
            'selectedCompany' => ['required', 'exists:companies,id'],
        ]);

        $company = Company::query()->find($this->selectedCompany);
        if (! $company) {
            return;
        }

        CompanyContext::setCompany($company);

        $this->redirect(Filament::getUrl());
    }
}
