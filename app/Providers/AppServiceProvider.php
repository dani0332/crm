<?php

namespace App\Providers;

use App\Enums\EnvEnum;
use App\Jobs\LeadAllocationJob;
use App\Services\LeadAllocationService;
use Barryvdh\Debugbar\Facade as Debugbar;
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
        $this->app->bind(LeadAllocationJob::class, function ($app) {
            return new LeadAllocationService($app->make(LeadAllocationService::class));
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $allowedEnvs = [EnvEnum::LOCAL, EnvEnum::DEVELOPMENT, EnvEnum::STAGING];
        if (in_array(config('APP_ENV','production'), $allowedEnvs)) {
            Debugbar::enable();
        }
        // DB::listen(function($query) {
        //     Log::info(
        //         $query->sql,
        //         $query->bindings,
        //         $query->time
        //     );
        // });
    }
}
