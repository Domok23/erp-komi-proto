<?php

namespace App\Filament\Pages;

use App\Services\CompanyContext;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanySettings extends Page
{
    protected static \UnitEnum|string|null $navigationGroup = 'Settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Company Settings';

    protected static ?string $title = 'Company Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'company-settings';

    protected static ?string $label = 'Company Settings';

    protected string $view = 'filament.pages.company-settings';

    public ?array $data = [];

    public function mount(): void
    {
        if (! CompanyContext::hasCompany()) {
            $this->redirect(route('filament.admin.pages.select-company'), navigate: true);

            return;
        }

        $company = CompanyContext::getCompany();
        if (! $company) {
            $this->redirect(route('filament.admin.pages.select-company'), navigate: true);

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
                Section::make('Company Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('code')
                                    ->label('Company Code')
                                    ->required()
                                    ->maxLength(20)
                                    ->disabled(),
                                TextInput::make('name')
                                    ->label('Company Name')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Select::make('type')
                                    ->label('Type')
                                    ->disabled()
                                    ->options([
                                        'main' => 'Main (Production)',
                                        'branch' => 'Branch (Warehouse)',
                                    ]),
                                TextInput::make('city')
                                    ->label('City')
                                    ->maxLength(100),
                            ]),
                    ]),

                Section::make('Contact')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('phone')
                                    ->label('Phone')
                                    ->tel()
                                    ->maxLength(20),
                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->maxLength(100),
                            ]),
                        TextInput::make('address')
                            ->label('Address')
                            ->columnSpanFull(),
                    ]),

                Section::make('Tax Identity')
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
            ->title('Changes saved')
            ->body('Company settings updated successfully.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Changes')
                ->button()
                ->color('primary')
                ->action('save'),
        ];
    }
}
