<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class CustomFileTypeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Validator::extend('custom_file_type', function ($attribute, $value, $parameters, $validator) {
            $acceptedFileTypes = explode(',', $parameters[0]);
            $extension = strtolower($value->getClientOriginalExtension());
            return in_array(".".$extension, $acceptedFileTypes);
        });
    }
}
