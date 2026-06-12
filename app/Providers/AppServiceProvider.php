<?php

namespace App\Providers;

use App\Models\BikeQuote;
use App\Models\BusinessQuote;
use App\Models\BusinessQuoteRequestDetail;
use App\Models\CarQuote;
use App\Models\CarQuoteRequestDetail;
use App\Models\ClaimRequest;
use App\Models\ClaimRequestDetail;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CycleQuote;
use App\Models\HealthQuote;
use App\Models\HealthQuoteRequestDetail;
use App\Models\LifeQuote;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Models\PersonalQuote;
use App\Models\PetQuote;
use App\Models\PolicyIssuance;
use App\Models\SendUpdateLog;
use App\Models\TravelQuote;
use App\Models\TravelQuoteRequestDetail;
use App\Models\YachtQuote;
use App\Observers\BikeQuoteObserver;
use App\Observers\BusinessQuoteDetailObserver;
use App\Observers\BusinessQuoteObserver;
use App\Observers\CarQuoteDetailObserver;
use App\Observers\CarQuoteObserver;
use App\Observers\ClaimRequestDetailObserver;
use App\Observers\ClaimRequestObserver;
use App\Observers\CustomerAddressObserver;
use App\Observers\CustomerObserver;
use App\Observers\CycleQuoteObserver;
use App\Observers\HealthQuoteDetailObserver;
use App\Observers\HealthQuoteObserver;
use App\Observers\LifeQuoteObserver;
use App\Observers\PaymentObserver;
use App\Observers\PaymentSplitsObserver;
use App\Observers\PersonalQuoteObserver;
use App\Observers\PetQuoteObserver;
use App\Observers\PolicyIssuanceObserver;
use App\Observers\SendUpdateLogObserver;
use App\Observers\TravelQuoteDetailObserver;
use App\Observers\TravelQuoteObserver;
use App\Observers\YachtQuoteObserver;
use App\Queue\MyAlfredSqsConnector;
use App\Services\BranchAssignmentService;
use App\Services\CsvExportService;
use App\Services\EmailExportService;
use App\Services\LeadsCountService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singletonIf(LeadsCountService::class, function ($app) {
            return new LeadsCountService;
        });

        // Register new CSV export services
        $this->app->singleton(CsvExportService::class);
        $this->app->singleton(EmailExportService::class);

        // Register Branch Assignment Service
        $this->app->singleton(BranchAssignmentService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Queue::extend('sqs_myalfred', fn () => new MyAlfredSqsConnector);

        CarQuote::observe(CarQuoteObserver::class);
        HealthQuote::observe(HealthQuoteObserver::class);
        LifeQuote::observe(LifeQuoteObserver::class);
        TravelQuote::observe(TravelQuoteObserver::class);
        BusinessQuote::observe(BusinessQuoteObserver::class);
        CarQuoteRequestDetail::observe(CarQuoteDetailObserver::class);
        HealthQuoteRequestDetail::observe(HealthQuoteDetailObserver::class);
        TravelQuoteRequestDetail::observe(TravelQuoteDetailObserver::class);
        BusinessQuoteRequestDetail::observe(BusinessQuoteDetailObserver::class);
        PetQuote::observe(PetQuoteObserver::class);
        YachtQuote::observe(YachtQuoteObserver::class);
        CycleQuote::observe(CycleQuoteObserver::class);
        BikeQuote::observe(BikeQuoteObserver::class);
        PersonalQuote::observe(PersonalQuoteObserver::class);
        Customer::observe(CustomerObserver::class);
        Payment::observe(PaymentObserver::class);
        PaymentSplits::observe(PaymentSplitsObserver::class);
        CustomerAddress::observe(CustomerAddressObserver::class);
        SendUpdateLog::observe(SendUpdateLogObserver::class);

        // Claim Request Observers
        ClaimRequest::observe(ClaimRequestObserver::class);
        ClaimRequestDetail::observe(ClaimRequestDetailObserver::class);
        // TODO: this PolicyIssuanceObserver is not for PROD.
        PolicyIssuance::observe(PolicyIssuanceObserver::class);
        // DB::listen(function($query) {
        //     info(
        //         $query->sql,
        //         $query->bindings,
        //         $query->time
        //     );
        // });

        if ($this->app->runningInConsole()) {
            Log::withContext([
                'trace_id' => (string) Str::uuid(),
                'is_console_command' => true,
            ]);
        }
    }
}
