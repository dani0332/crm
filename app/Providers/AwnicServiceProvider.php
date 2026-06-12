<?php

namespace App\Providers;

use App\Services\PolicyIssuanceAutomation\Cyber\AwnicBookPolicyService;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicHttpClient;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicInsuranceService;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicResponseHandler;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicStepExecutor;
use App\Services\PolicyIssuanceAutomation\Cyber\AwnicValidationService;
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
        App::bind(AwnicInsuranceService::class, function ($app) {
            return new AwnicInsuranceService(
                $app->make(AwnicStepExecutor::class),
                $app->make(AwnicValidationService::class),
                $app->make(AwnicBookPolicyService::class),
                $app->make(AwnicResponseHandler::class),
            );
        });
    }
}
