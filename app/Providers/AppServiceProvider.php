<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Models\Product;
use App\Models\Service;
use App\Models\RentProperty;
use App\Models\SaleProperty;
use App\Observers\ProductObserver;

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
        // One Observer to rule them all
        // This handles New Listings and Price Drops for every category in Sbrai Hub
        Product::observe(ProductObserver::class);
        Service::observe(ProductObserver::class);
        RentProperty::observe(ProductObserver::class);
        SaleProperty::observe(ProductObserver::class);
    }
}
