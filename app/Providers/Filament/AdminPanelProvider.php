<?php

namespace App\Providers\Filament;

use App\Filament\Pages\CompanySettings;
use App\Filament\Pages\SelectCompany;
use App\Http\Middleware\EnsureCompanySelected;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('')
            ->brandName('ERP Komi Proto')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->navigationGroups([
                'Master Data',
                'R&D & Consumption',
                'Projects',
                'Merchandising',
                'Costing & Pricing',
                'Sales & Shipping',
                'Procurement',
                'Inventory & Subcon',
                'Finance & Invoices',
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                SelectCompany::class,
                CompanySettings::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureCompanySelected::class,
            ])
            ->userMenuItems([
                \Filament\Navigation\MenuItem::make()
                    ->label('Switch Company')
                    ->url(fn (): string => '/select-company?switch=1')
                    ->icon('heroicon-o-arrows-right-left'),
            ])
            ->darkMode(false)
            ->sidebarCollapsibleOnDesktop()
            ->registration();
    }
}
