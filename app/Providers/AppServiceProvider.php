<?php

namespace App\Providers;

use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\LifeQuote;
use App\Models\TravelQuote;
use App\Observers\BusinessQuoteObserver;
use App\Observers\CarQuoteObserver;
use App\Observers\HealthQuoteObserver;
use App\Observers\HomeQuoteObserver;
use App\Observers\LifeQuoteObserver;
use App\Observers\TravelQuoteObserver;
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
        HealthQuote::observe(HealthQuoteObserver::class);
        HomeQuote::observe(HomeQuoteObserver::class);
        LifeQuote::observe(LifeQuoteObserver::class);
        TravelQuote::observe(TravelQuoteObserver::class);
        BusinessQuote::observe(BusinessQuoteObserver::class);
        // DB::listen(function($query) {
        //     info(
        //         $query->sql,
        //         $query->bindings,
        //         $query->time
        //     );
        // });
    }
}
