<?php

namespace App\Providers;

use App\Services\InstantWriterAIAPIService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class InstantWriterAIAPIServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        App::bind('InstantWriterAIAPIService', function () {
            return new InstantWriterAIAPIService;
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
