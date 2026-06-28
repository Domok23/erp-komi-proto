<?php

namespace App\Providers;

use App\Models\GoodsReceiptShipping;
use App\Models\InvoicePurchase;
use App\Models\InvoiceSales;
use App\Models\Payment;
use App\Models\PoSubcon;
use App\Models\PoSupplier;
use App\Observers\PaymentObserver;
use Filament\Support\Facades\FilamentView;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'supplier' => PoSupplier::class,
            'subcon' => PoSubcon::class,
            'po_supplier' => PoSupplier::class,
            'po_subcon' => PoSubcon::class,
            'gr_shipping' => GoodsReceiptShipping::class,
            'purchase' => InvoicePurchase::class,
            'sales' => InvoiceSales::class,
        ]);

        Payment::observe(PaymentObserver::class);

        FilamentView::registerRenderHook(
            'panels::head.end',
            fn (): HtmlString => new HtmlString('
                <style>
                    /* Prevent select form text from wrapping and making the form taller */
                    .fi-select-input-btn {
                        min-width: 0 !important;
                        display: flex !important;
                        align-items: center !important;
                        width: 100% !important;
                    }
                    .fi-select-input-value-ctn {
                        min-width: 0 !important;
                        flex: 1 1 auto !important;
                        overflow: hidden !important;
                        text-overflow: ellipsis !important;
                        white-space: nowrap !important;
                        display: block !important;
                        text-align: left !important;
                    }
                    .fi-select-input-value-label {
                        white-space: nowrap !important;
                        overflow: hidden !important;
                        text-overflow: ellipsis !important;
                        display: block !important;
                        max-width: 100% !important;
                    }
                    /* Hide project code in selected label but show in dropdown options list */
                    .fi-select-input-value-label .project-code-prefix {
                        display: none !important;
                    }
                    .fi-dropdown-list-item .project-code-prefix,
                    .fi-select-input-option .project-code-prefix {
                        display: inline !important;
                        font-weight: 500 !important;
                        opacity: 0.6 !important;
                    }
                    .choices__list--single,
                    .choices__list--single .choices__item,
                    .choices__item--selectable {
                        white-space: nowrap !important;
                        overflow: hidden !important;
                        text-overflow: ellipsis !important;
                        max-width: calc(100% - 2.5rem) !important;
                        display: block !important;
                    }
                    /* Style selected option in dropdown list using dynamic primary color */
                    .fi-dropdown-list-item.fi-selected,
                    .fi-select-input-option.fi-selected {
                        background-color: var(--primary-500, var(--color-primary-500, #f59e0b)) !important;
                        color: #ffffff !important; /* White text for contrast */
                    }
                    .fi-dropdown-list-item.fi-selected:hover,
                    .fi-select-input-option.fi-selected:hover {
                        background-color: var(--primary-600, var(--color-primary-600, #d97706)) !important;
                        color: #ffffff !important;
                    }
                    /* Ensure the parent Choices.js container behaves correctly */
                    .choices__inner {
                        min-height: auto !important;
                        display: flex !important;
                        align-items: center !important;
                        overflow: hidden !important;
                    }
                </style>
            ')
        );
    }
}
