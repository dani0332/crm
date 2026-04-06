<?php

namespace App\Providers;

use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicBookPolicyService;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicHttpClient;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicInsuranceService;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicResponseHandler;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicStepExecutor;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicValidationService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class AdnicServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        App::bind(AdnicHttpClient::class, function () {
            return new AdnicHttpClient;
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        App::bind(AdnicInsuranceService::class, function ($app) {
            return new AdnicInsuranceService(
                $app->make(AdnicStepExecutor::class),
                $app->make(AdnicValidationService::class),
                $app->make(AdnicBookPolicyService::class),
                $app->make(AdnicResponseHandler::class),
            );
        });
    }
}
