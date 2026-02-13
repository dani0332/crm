<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiApiService;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiBookPolicyService;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiDocumentHandler;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiHttpClient;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiInsuranceService;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiQuoteUpdaterService;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiRequestBuilder;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiResponseHandler;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiStepExecutor;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiValidationService;
use App\Services\PolicyIssuanceFailureEmailService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class NgiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        App::bind('NgiHttpClient', function () {
            return new NgiHttpClient;
        });

        App::bind(NgiDocumentHandler::class, function ($app) {
            return new NgiDocumentHandler(
                $app->make(NgiHttpClient::class)
            );
        });

        App::bind(NgiValidationService::class, function ($app) {
            return new NgiValidationService;
        });

        App::bind(NgiApiService::class, function ($app) {
            return new NgiApiService(
                $app->make(NgiRequestBuilder::class),
                $app->make(NgiResponseHandler::class),
                $app->make(NgiQuoteUpdaterService::class),
                $app->make(NgiValidationService::class),
                $app->make(PolicyIssuanceFailureEmailService::class),
            );
        });

        App::bind(NgiBookPolicyService::class, function ($app) {
            return new NgiBookPolicyService(
                $app->make(NgiValidationService::class),
                $app->make(NgiResponseHandler::class),
                $app->make(NgiQuoteUpdaterService::class),
            );
        });

        App::bind(NgiStepExecutor::class, function ($app) {
            return new NgiStepExecutor(
                $app->make(NgiApiService::class),
                $app->make(NgiBookPolicyService::class),
                $app->make(PolicyIssuanceFailureEmailService::class),
            );
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        App::bind(NgiInsuranceService::class, function ($app) {
            return new NgiInsuranceService(
                $app->make(NgiStepExecutor::class),
                $app->make(NgiValidationService::class),
                $app->make(NgiBookPolicyService::class),
                $app->make(NgiResponseHandler::class)
            );
        });
    }
}
