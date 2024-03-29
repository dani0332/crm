<?php

namespace App\Providers;

use App\Models\BusinessQuote;
use App\Models\BusinessQuoteRequestDetail;
use App\Models\CarQuote;
use App\Models\CarQuoteRequestDetail;
use App\Models\HealthQuote;
use App\Models\HealthQuoteRequestDetail;
use App\Models\HomeQuote;
use App\Models\HomeQuoteRequestDetail;
use App\Models\LifeQuote;
use App\Models\LifeQuoteRequestDetail;
use App\Models\TravelQuote;
use App\Models\TravelQuoteRequestDetail;
use App\Observers\BusinessQuoteObserver;
use App\Observers\CarQuoteObserver;
use App\Observers\HealthQuoteObserver;
use App\Observers\HomeQuoteObserver;
use App\Observers\LifeQuoteObserver;
use App\Observers\TravelQuoteObserver;
use App\Services\CarAllocationService;
use App\Services\HealthAllocationService;
use Illuminate\Support\ServiceProvider;
use App\Observers\CarQuoteDetailObserver;
use App\Observers\HealthQuoteDetailObserver;
use App\Observers\HomeQuoteDetailObserver;
use App\Observers\LifeQuoteDetailObserver;
use App\Observers\TravelQuoteDetailObserver;
use App\Observers\BusinessQuoteDetailObserver;

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
        CarQuoteRequestDetail::observe(CarQuoteDetailObserver::class);
        HealthQuoteRequestDetail::observe(HealthQuoteDetailObserver::class);
        HomeQuoteRequestDetail::observe(HomeQuoteDetailObserver::class);
        LifeQuoteRequestDetail::observe(LifeQuoteDetailObserver::class);
        TravelQuoteRequestDetail::observe(TravelQuoteDetailObserver::class);
        BusinessQuoteRequestDetail::observe(BusinessQuoteDetailObserver::class);
        // DB::listen(function($query) {
        //     info(
        //         $query->sql,
        //         $query->bindings,
        //         $query->time
        //     );
        // });
    }
}
