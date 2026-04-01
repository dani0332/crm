<?php

namespace App\Providers;

use App\Services\CustomerPortalApiService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class CustomerPortalApiProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        App::bind('CustomerPortalApiService', function () {
            return new CustomerPortalApiService;
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
