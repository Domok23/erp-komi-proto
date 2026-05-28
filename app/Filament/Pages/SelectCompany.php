<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Services\CompanyContext;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\SimplePage;

class SelectCompany extends SimplePage
{
    protected static string $view = 'filament.pages.select-company';

    protected static ?string $title = 'Pilih Perusahaan';

    protected static ?string $navigationLabel = 'Pilih Perusahaan';

    protected static ?string $slug = 'select-company';

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
            'companies' => Company::where('is_active', true)
                ->orderBy('type')
                ->orderBy('name')
                ->get(),
        ];
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('selectedCompany')
                ->label('Pilih Perusahaan')
                ->options(
                    Company::where('is_active', true)
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

        $company = Company::find($this->selectedCompany);
        if (! $company) {
            return;
        }

        CompanyContext::setCompany($company);

        $this->redirect(Filament::getUrl());
    }
}
