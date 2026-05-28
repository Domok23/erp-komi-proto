<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Services\CompanyContext;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class CompanySettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Pengaturan Perusahaan';
    protected static ?string $title = 'Pengaturan Perusahaan';
    protected static ?string $slug = 'company-settings';
    protected static ?string $label = 'Pengaturan Perusahaan';

    protected string $view = 'filament.pages.company-settings';

    public ?array $data = [];

    public function mount(): void
    {
        if (! CompanyContext::hasCompany()) {
            $this->redirect(route('filament.admin.pages.select-company'));

            return;
        }

        $company = CompanyContext::getCompany();
        if (! $company) {
            $this->redirect(route('filament.admin.pages.select-company'));

            return;
        }

        $this->form->fill([
            'code' => $company->code,
            'name' => $company->name,
            'address' => $company->address ?? '',
            'city' => $company->city ?? '',
            'phone' => $company->phone ?? '',
            'email' => $company->email ?? '',
            'npwp' => $company->npwp ?? '',
            'type' => $company->type ?? 'main',
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->statePath('data')
            ->schema([
                Section::make('Informasi Perusahaan')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('code')
                                    ->label('Kode Perusahaan')
                                    ->required()
                                    ->maxLength(20)
                                    ->disabled(),
                                TextInput::make('name')
                                    ->label('Nama Perusahaan')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Select::make('type')
                                    ->label('Tipe')
                                    ->disabled()
                                    ->options([
                                        'main' => 'Main (Produksi)',
                                        'branch' => 'Branch (Gudang)',
                                    ]),
                                TextInput::make('city')
                                    ->label('Kota')
                                    ->maxLength(100),
                            ]),
                    ]),

                Section::make('Kontak')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('phone')
                                    ->label('Telepon')
                                    ->tel()
                                    ->maxLength(20),
                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->maxLength(100),
                            ]),
                        TextInput::make('address')
                            ->label('Alamat')
                            ->columnSpanFull(),
                    ]),

                Section::make('Identitas Pajak')
                    ->schema([
                        TextInput::make('npwp')
                            ->label('NPWP')
                            ->maxLength(30)
                            ->placeholder('__.___.____._.___.____'),
                    ]),
            ]);
    }

    public function save(): void
    {
        $company = CompanyContext::getCompany();
        if (! $company) {
            return;
        }

        $validated = $this->form->getState();

        $company->update([
            'name' => $validated['name'] ?? $company->name,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'npwp' => $validated['npwp'] ?? null,
        ]);

        Notification::make()
            ->title('Perubahan disimpan')
            ->body('Pengaturan perusahaan berhasil diperbarui.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Perubahan')
                ->button()
                ->color('primary')
                ->action('save'),
        ];
    }
}