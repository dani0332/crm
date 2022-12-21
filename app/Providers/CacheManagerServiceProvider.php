<?php

namespace App\Providers;

use App\Services\CacheManager;
use Illuminate\Support\ServiceProvider;

class CacheManagerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('cachemanager', function () {
            return new CacheManager;
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
