<?php

namespace App\Providers;

use App\Services\PostMarkService;
use Illuminate\Support\ServiceProvider;

class PostMarkServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('PostMark', function ($app) {
            return new PostMarkService;
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
