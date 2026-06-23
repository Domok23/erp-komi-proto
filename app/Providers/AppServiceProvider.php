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
    }
}
