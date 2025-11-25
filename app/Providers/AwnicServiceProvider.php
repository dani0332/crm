<?php

namespace App\Providers;

use App\Services\PolicyIssuanceAutomation\Cyber\AwnicHttpClient;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class AwnicServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        App::bind('AwnicHttpClient', function () {
            return new AwnicHttpClient;
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
