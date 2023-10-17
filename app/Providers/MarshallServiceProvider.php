<?php

namespace App\Providers;

use App\Services\MarshallService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class MarshallServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        App::bind('marshsall', function () {
            return new MarshallService;
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
