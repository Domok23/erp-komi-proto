<?php

namespace App\Providers;

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
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'supplier' => \App\Models\PoSupplier::class,
            'subcon' => \App\Models\PoSubcon::class,
            'po_supplier' => \App\Models\PoSupplier::class,
            'po_subcon' => \App\Models\PoSubcon::class,
            'gr_shipping' => \App\Models\GoodsReceiptShipping::class,
            'purchase' => \App\Models\InvoicePurchase::class,
            'sales' => \App\Models\InvoiceSales::class,
        ]);

        \Filament\Support\Facades\FilamentView::registerRenderHook(
            'panels::head.end',
            fn (): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString('
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
