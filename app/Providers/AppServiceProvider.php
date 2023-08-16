<?php

namespace App\Providers;

use App\Models\CarQuote;
use App\Observers\CarAssignmentObserver;
use App\Services\CarAllocationService;
use Illuminate\Support\Facades\DB;
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
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        CarQuote::observe(CarAssignmentObserver::class);
        // DB::listen(function($query) {
        //     info(
        //         $query->sql,
        //         $query->bindings,
        //         $query->time
        //     );
        // });
    }
}
