<?php

namespace App\Providers\Filament;

use App\Filament\Pages\CompanySettings;
use App\Filament\Pages\EditProfile;
use App\Filament\Pages\SelectCompany;
use App\Filament\Widgets\ErpStatsWidget;
use App\Filament\Widgets\LowStockMaterials;
use App\Filament\Widgets\ProjectStatusChart;
use App\Filament\Widgets\RecentPurchaseOrders;
use App\Filament\Widgets\RecentSalesOrders;
use App\Filament\Widgets\SalesTrendChart;
use App\Http\Middleware\EnsureCompanySelected;
use App\Livewire\CustomDatabaseNotifications;
use App\Services\CompanyContext;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
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
                NavigationGroup::make('Master Data')->collapsible(true),
                NavigationGroup::make('R&D & Consumption')->collapsible(true),
                NavigationGroup::make('Projects')->collapsible(true),
                NavigationGroup::make('Merchandising')->collapsible(true),
                NavigationGroup::make('Costing & Pricing')->collapsible(true),
                NavigationGroup::make('Sales & Shipping')->collapsible(true),
                NavigationGroup::make('Procurement')->collapsible(true),
                NavigationGroup::make('Production')->collapsible(true),
                NavigationGroup::make('Inventory & Subcon')->collapsible(true),
                NavigationGroup::make('Finance & Invoices')->collapsible(true),
                NavigationGroup::make('HR')->collapsible(true),
                NavigationGroup::make('Settings')->collapsible(true),
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                SelectCompany::class,
                CompanySettings::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                ErpStatsWidget::class,
                SalesTrendChart::class,
                ProjectStatusChart::class,
                RecentSalesOrders::class,
                RecentPurchaseOrders::class,
                LowStockMaterials::class,
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
            ->databaseNotifications(livewireComponent: CustomDatabaseNotifications::class)
            ->registration();
    }
}
