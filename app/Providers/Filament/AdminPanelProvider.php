<?php

namespace App\Providers\Filament;

use App\Filament\Pages\CompanySettings;
use App\Filament\Pages\EditProfile;
use App\Filament\Pages\SelectCompany;
use App\Http\Middleware\EnsureCompanySelected;
use App\Services\CompanyContext;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->spa()
            ->path('')
            ->brandName(fn () => CompanyContext::getCompany()?->brand_name ?? CompanyContext::getCompany()?->name ?? 'ERP Komi Proto')
            ->brandLogo(fn () => CompanyContext::getCompany()?->logo_path ? Storage::disk('public')->url(CompanyContext::getCompany()->logo_path) : null)
            ->brandLogoHeight('2.5rem')
            ->login()
            ->colors([
                'primary' => Color::Amber,
                'secondary' => Color::Slate,
                'amber' => Color::Amber,
                'purple' => Color::Purple,
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
                'Production',
                'Inventory & Subcon',
                'Finance & Invoices',
                'HR',
                'Settings',
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                SelectCompany::class,
                CompanySettings::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                \App\Filament\Widgets\ErpStatsWidget::class,
                \App\Filament\Widgets\SalesTrendChart::class,
                \App\Filament\Widgets\RecentSalesOrders::class,
                \App\Filament\Widgets\RecentPurchaseOrders::class,
                \App\Filament\Widgets\LowStockMaterials::class,
            ])
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
                MenuItem::make()
                    ->label('Switch Company')
                    ->url(fn (): string => '/select-company?switch=1')
                    ->icon('heroicon-o-arrows-right-left'),
            ])
            ->darkMode(true)
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => "<script>
                    (function() {
                        if (!localStorage.getItem('theme')) {
                            localStorage.setItem('theme', 'light');
                            document.documentElement.classList.remove('dark');
                        }
                    })();
                </script>"
            )
            ->sidebarCollapsibleOnDesktop()
            ->profile(EditProfile::class, isSimple: false)
            ->databaseNotifications(livewireComponent: \App\Livewire\CustomDatabaseNotifications::class)
            ->registration();
    }
}
