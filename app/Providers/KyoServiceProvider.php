<?php

namespace App\Providers;

use App\Services\KyoService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class KyoServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        App::bind('kyo', function () {
            return new KyoService;
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
