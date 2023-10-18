<?php

namespace App\Providers;

use App\Models\CarQuote;
use App\Observers\CarQuoteObserver;
use App\Services\CarAllocationService;
use App\Services\HealthAllocationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(CarAllocationService::class, function ($app) {
            return new CarAllocationService();
        });

        $this->app->bind(HealthAllocationService::class, function ($app) {
            return new HealthAllocationService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        CarQuote::observe(CarQuoteObserver::class);
        // DB::listen(function($query) {
        //     info(
        //         $query->sql,
        //         $query->bindings,
        //         $query->time
        //     );
        // });
    }
}
