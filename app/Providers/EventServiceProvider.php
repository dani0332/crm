<?php

namespace App\Providers;

use App\Events\BikeQuoteAdvisorUpdated;
use App\Events\CarQuoteAdvisorUpdated;
use App\Events\Health\HealthTransactionApproved;
use App\Events\QuoteEmailUpdated;
use App\Events\TravelQuoteAdvisorUpdated;
use App\Listeners\HandleBikeAdvisorUpdated;
use App\Listeners\HandleCarAdvisorUpdated;
use App\Listeners\Health\HandleHealthTransactionApproved;
use App\Listeners\HandleTravelAdvisorUpdated;
use App\Listeners\LoginListener;
use App\Listeners\LogoutListener;
use App\Listeners\UpdateCustomerEmail;
use App\Models\RenewalBatch;
use App\Observers\RenewalBatchObserver;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        CarQuoteAdvisorUpdated::class => [
            HandleCarAdvisorUpdated::class,
        ],
        TravelQuoteAdvisorUpdated::class => [
            HandleTravelAdvisorUpdated::class,
        ],
        Login::class => [
            LoginListener::class,
        ],
        Logout::class => [
            LogoutListener::class,
        ],
        QuoteEmailUpdated::class => [
            UpdateCustomerEmail::class,
        ],
        BikeQuoteAdvisorUpdated::class => [
            HandleBikeAdvisorUpdated::class,
        ],
        HealthTransactionApproved::class => [
            HandleHealthTransactionApproved::class,
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        RenewalBatch::observe(RenewalBatchObserver::class);
    }
}
