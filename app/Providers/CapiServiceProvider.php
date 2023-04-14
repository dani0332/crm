<?php

namespace App\Providers;

use App\Services\CapiService;
use Illuminate\Support\ServiceProvider;

class CapiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        App::bind('capi', function () {
            return new CapiService;
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
