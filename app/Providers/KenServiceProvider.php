<?php

namespace App\Providers;

use App\Services\KenService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class KenServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        App::bind('ken', function () {
            return new KenService;
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
